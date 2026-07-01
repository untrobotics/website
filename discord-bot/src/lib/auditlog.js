'use strict';

const { pool } = require('../db');
const log = require('../logger');

/**
 * Append one row to the `discord_verification_log` audit trail.
 *
 * This is BEST-EFFORT: it is wrapped in try/catch and NEVER throws, so a
 * logging failure can never break verification. The plaintext code and the
 * code hash are NEVER passed in or stored here.
 *
 * @param {Object}  entry
 * @param {string}  entry.discordId  Discord user id (snowflake).
 * @param {string} [entry.username]  Discord tag (e.g. "name#0001"), if known.
 * @param {string} [entry.email]     Email involved in the action, if any.
 * @param {string}  entry.action     'verify_request' | 'token_attempt'.
 * @param {string}  entry.outcome    Terminal outcome (see schema comment).
 * @param {string} [entry.detail]    Free-form context (attemptsLeft, etc.).
 */
async function logAttempt({ discordId, username, email, action, outcome, detail } = {}) {
  try {
    await pool.query(
      `INSERT INTO discord_verification_log
         (discord_id, username, email, action, outcome, detail)
       VALUES (?, ?, ?, ?, ?, ?)`,
      [
        discordId,
        username != null ? String(username).slice(0, 255) : null,
        email != null ? String(email).slice(0, 255) : null,
        action,
        outcome,
        detail != null ? String(detail).slice(0, 255) : null,
      ]
    );
  } catch (err) {
    // Never rethrow — the audit trail is auxiliary and must not affect the
    // verification flow.
    log.warn('auditlog: failed to record attempt', err && err.message);
  }
}

module.exports = { logAttempt };
