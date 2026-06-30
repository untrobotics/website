'use strict';

const crypto = require('crypto');
const { pool } = require('../db');
const { config } = require('../config');
const ratelimit = require('./ratelimit');
const { sendVerificationCode } = require('./email');
const log = require('../logger');

/* -------------------------------------------------------------------------- */
/* Helpers                                                                     */
/* -------------------------------------------------------------------------- */

/** Lower-case and trim an email. */
function normalizeEmail(email) {
  return String(email || '').trim().toLowerCase();
}

/** Loose structural email check (the real gate is the domain allow-list). */
function looksLikeEmail(email) {
  return /^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email);
}

/**
 * Exact-domain allow-list check (NOT a substring match — "notunt.edu" must not
 * pass for "unt.edu"). The part after the last '@' must equal an allowed domain.
 */
function isAllowedDomain(email) {
  const at = email.lastIndexOf('@');
  if (at < 0) return false;
  const domain = email.slice(at + 1).toLowerCase();
  return config.allowedEmailDomains.includes(domain);
}

/** Cryptographically-random zero-padded numeric code of `config.codeLength`. */
function generateCode() {
  const max = 10 ** config.codeLength; // exclusive upper bound
  const n = crypto.randomInt(0, max); // uniform, no modulo bias
  return String(n).padStart(config.codeLength, '0');
}

/**
 * Hash a code for storage. We never store the plaintext. Binding the hash to
 * the discord_id means an identical code for two users hashes differently, and
 * mixing in a server-side secret means a DB leak alone can't be brute-forced
 * offline as easily.
 */
function hashCode(code, discordId) {
  return crypto
    .createHash('sha256')
    .update(`${code}:${discordId}:${config.hashSecret}`)
    .digest('hex');
}

/** Constant-time comparison of two hex digests of equal length. */
function hashesEqual(a, b) {
  if (typeof a !== 'string' || typeof b !== 'string' || a.length !== b.length) {
    return false;
  }
  return crypto.timingSafeEqual(Buffer.from(a, 'hex'), Buffer.from(b, 'hex'));
}

/** Fetch the verification row for a discord id (or null). */
async function getRow(discordId) {
  const [rows] = await pool.query(
    'SELECT * FROM discord_verifications WHERE discord_id = ? LIMIT 1',
    [discordId]
  );
  return rows.length ? rows[0] : null;
}

/* -------------------------------------------------------------------------- */
/* /verify — request a code                                                    */
/* -------------------------------------------------------------------------- */

/**
 * Validate + rate-limit + email a fresh code.
 *
 * Returns { ok, reason, retryAfter?, email? }. The command layer maps `reason`
 * to a user-facing (ephemeral) message; the plaintext code is NEVER returned.
 *
 * reasons: invalid_email, bad_domain, already_verified, email_taken,
 *          cooldown, hourly_limit, email_failed, sent
 */
async function requestCode(discordId, rawEmail) {
  const email = normalizeEmail(rawEmail);

  if (!looksLikeEmail(email)) return { ok: false, reason: 'invalid_email' };
  if (!isAllowedDomain(email)) return { ok: false, reason: 'bad_domain' };

  const row = await getRow(discordId);
  if (row && row.verified) return { ok: false, reason: 'already_verified' };

  // Prevent account sharing: an email may only ever bind to one discord id.
  const [taken] = await pool.query(
    'SELECT discord_id FROM discord_verifications WHERE email = ? AND discord_id <> ? LIMIT 1',
    [email, discordId]
  );
  if (taken.length) return { ok: false, reason: 'email_taken' };

  const now = new Date();

  // Fast in-memory cooldown guard (also blocks concurrent double-sends before
  // the DB row is updated). Authoritative window state still lives in the row.
  const cdKey = `verify:${discordId}`;
  const cdRemaining = ratelimit.secondsRemaining(cdKey);
  if (cdRemaining > 0) return { ok: false, reason: 'cooldown', retryAfter: cdRemaining };

  if (row && row.last_sent_at) {
    const since = (now - new Date(row.last_sent_at)) / 1000;
    if (since < config.verifyCooldownSeconds) {
      return {
        ok: false,
        reason: 'cooldown',
        retryAfter: Math.ceil(config.verifyCooldownSeconds - since),
      };
    }
  }

  // Per-hour send window.
  let windowStartedAt = row && row.window_started_at ? new Date(row.window_started_at) : null;
  let sendCount = row ? row.send_count_window || 0 : 0;
  const windowAgeSec = windowStartedAt ? (now - windowStartedAt) / 1000 : Infinity;
  if (windowAgeSec >= 3600) {
    // Window expired — start a new one.
    windowStartedAt = now;
    sendCount = 0;
  }
  if (sendCount >= config.maxVerifyPerHour) {
    const retryAfter = Math.ceil(3600 - windowAgeSec);
    return { ok: false, reason: 'hourly_limit', retryAfter };
  }

  // Arm the cooldown immediately so a rapid second invocation can't double-send
  // while we await the email round-trip.
  ratelimit.arm(cdKey, config.verifyCooldownSeconds);

  const code = generateCode();
  const codeHash = hashCode(code, discordId);
  const expiresAt = new Date(now.getTime() + config.codeTtlSeconds * 1000);

  // Send the email BEFORE persisting so a SendGrid failure doesn't leave the
  // user with a code they never received (and doesn't consume their budget).
  try {
    await sendVerificationCode(email, code, Math.round(config.codeTtlSeconds / 60));
  } catch (err) {
    ratelimit.clear(cdKey); // send failed — don't penalise the user
    log.error('verification: email send failed', err.message);
    return { ok: false, reason: 'email_failed' };
  }

  // Upsert the row: bind email, store the hash, reset attempts, bump window.
  await pool.query(
    `INSERT INTO discord_verifications
       (discord_id, email, code_hash, expires_at, attempts, last_sent_at,
        send_count_window, window_started_at)
     VALUES (?, ?, ?, ?, 0, ?, ?, ?)
     ON DUPLICATE KEY UPDATE
       email = VALUES(email),
       code_hash = VALUES(code_hash),
       expires_at = VALUES(expires_at),
       attempts = 0,
       last_sent_at = VALUES(last_sent_at),
       send_count_window = VALUES(send_count_window),
       window_started_at = VALUES(window_started_at)`,
    [discordId, email, codeHash, expiresAt, now, sendCount + 1, windowStartedAt]
  );

  return { ok: true, reason: 'sent', email };
}

/* -------------------------------------------------------------------------- */
/* /token — redeem a code                                                      */
/* -------------------------------------------------------------------------- */

/**
 * Validate a submitted code. Returns { ok, reason, retryAfter?, email?,
 * attemptsLeft? }.
 *
 * reasons: no_request, already_verified, locked, attempt_cooldown,
 *          expired, invalid, verified
 *
 * `expired` and `invalid` both correspond to the SAME generic user message
 * ("invalid or expired code") so we never reveal which condition failed.
 */
async function redeemToken(discordId, submittedCode) {
  const row = await getRow(discordId);
  if (!row) return { ok: false, reason: 'no_request' };
  if (row.verified) return { ok: false, reason: 'already_verified' };

  const now = new Date();

  if (row.locked_until && new Date(row.locked_until) > now) {
    const retryAfter = Math.ceil((new Date(row.locked_until) - now) / 1000);
    return { ok: false, reason: 'locked', retryAfter };
  }

  // Per-attempt cooldown (in-memory, single instance).
  const cdKey = `token:${discordId}`;
  const cdRemaining = ratelimit.secondsRemaining(cdKey);
  if (cdRemaining > 0) {
    return { ok: false, reason: 'attempt_cooldown', retryAfter: cdRemaining };
  }
  ratelimit.arm(cdKey, config.tokenAttemptCooldownSeconds);

  // No active code, or expired → generic invalid/expired.
  if (!row.code_hash || !row.expires_at || new Date(row.expires_at) <= now) {
    return { ok: false, reason: 'expired' };
  }

  const submittedHash = hashCode(String(submittedCode || '').trim(), discordId);

  if (!hashesEqual(submittedHash, row.code_hash)) {
    const attempts = (row.attempts || 0) + 1;
    const failedTotal = (row.failed_total || 0) + 1;
    const invalidateCode = attempts >= config.maxTokenAttempts;
    const lock = failedTotal >= config.lockoutThreshold;
    const lockedUntil = lock
      ? new Date(now.getTime() + config.lockoutSeconds * 1000)
      : row.locked_until;

    await pool.query(
      `UPDATE discord_verifications
         SET attempts = ?,
             failed_total = ?,
             code_hash = ?,
             expires_at = ?,
             locked_until = ?
       WHERE discord_id = ?`,
      [
        invalidateCode ? 0 : attempts, // reset attempts once code is burned
        failedTotal,
        invalidateCode ? null : row.code_hash,
        invalidateCode ? null : row.expires_at,
        lockedUntil,
        discordId,
      ]
    );

    const attemptsLeft = Math.max(0, config.maxTokenAttempts - attempts);
    return {
      ok: false,
      reason: 'invalid',
      attemptsLeft,
      burned: invalidateCode,
      locked: lock,
      retryAfter: lock ? config.lockoutSeconds : undefined,
    };
  }

  // Correct! Mark verified and clear the active code.
  await pool.query(
    `UPDATE discord_verifications
       SET verified = 1,
           verified_at = ?,
           code_hash = NULL,
           expires_at = NULL,
           attempts = 0,
           locked_until = NULL
     WHERE discord_id = ?`,
    [now, discordId]
  );
  ratelimit.clear(cdKey);

  return { ok: true, reason: 'verified', email: row.email };
}

module.exports = {
  normalizeEmail,
  looksLikeEmail,
  isAllowedDomain,
  generateCode,
  hashCode,
  hashesEqual,
  getRow,
  requestCode,
  redeemToken,
};
