'use strict';

/**
 * Centralised configuration. Everything is read from the environment so the
 * same image runs under docker-compose locally and Kubernetes in the cluster.
 *
 * The bot is meant to be deployed with `envFrom: web-config + web-secrets`
 * (the same ConfigMap/Secret the PHP app uses), so we accept the app's existing
 * variable names as fall-backs:
 *   DISCORD_BOT_TOKEN  <- DISCORD_ADMIN_BOT_TOKEN   (reuse the admin bot token)
 *   DISCORD_CLIENT_ID  <- DISCORD_APP_CLIENT_ID
 *   DB_HOST/USER/...   <- DATABASE_HOST/USER/...
 *
 * Required values fail fast at startup (see `assertConfig` below) so a
 * mis-provisioned pod crash-loops loudly instead of running half-broken.
 */

// In local dev, load a .env file if one is present. In the container this is a
// no-op (dotenv is a devDependency and not installed with --omit=dev), so the
// require is wrapped defensively.
try {
  // eslint-disable-next-line global-require
  require('dotenv').config();
} catch (_) {
  /* dotenv not installed (production image) — env comes from k8s/compose */
}

/** Read an env var, returning `fallback` when unset/empty. */
function env(key, fallback = undefined) {
  const v = process.env[key];
  return v === undefined || v === '' ? fallback : v;
}

/** First non-empty env var among `keys`, else `fallback`. */
function envAny(keys, fallback = undefined) {
  for (const k of keys) {
    const v = process.env[k];
    if (v !== undefined && v !== '') return v;
  }
  return fallback;
}

/** Parse an integer env var, falling back to `fallback` on missing/invalid. */
function intEnv(key, fallback) {
  const v = process.env[key];
  if (v === undefined || v === '') return fallback;
  const n = parseInt(v, 10);
  return Number.isFinite(n) ? n : fallback;
}

const config = Object.freeze({
  // --- Discord identity -----------------------------------------------------
  // Reuse the existing admin bot token (DISCORD_ADMIN_BOT_TOKEN in web-secrets).
  token: envAny(['DISCORD_BOT_TOKEN', 'DISCORD_ADMIN_BOT_TOKEN']),
  clientId: envAny(['DISCORD_CLIENT_ID', 'DISCORD_APP_CLIENT_ID']),
  guildId: env('DISCORD_GUILD_ID', '639209564188704768'),

  // Verification feature targets.
  verifiedRoleId: env('DISCORD_VERIFIED_ROLE_ID'),
  verifyChannelId: env('DISCORD_VERIFY_CHANNEL_ID'),
  // Where non-UNT folks (other schools, HS, industry mentors) go for MANUAL
  // verification. Rendered as a clickable channel mention if set.
  verifyHelpChannelId: env('DISCORD_VERIFY_HELP_CHANNEL_ID'),
  // Channel that receives verification audit lines. Defaults to the admin
  // channel used by the PHP bot (api/discord/bots/admin.php).
  logChannelId: env('DISCORD_LOG_CHANNEL_ID', '674703370971250708'),

  // --- Email verification ---------------------------------------------------
  allowedEmailDomains: env(
    'ALLOWED_EMAIL_DOMAINS',
    'unt.edu,my.unt.edu,untsystem.edu,untdallas.edu,unthsc.edu'
  )
    .split(',')
    .map((d) => d.trim().toLowerCase())
    .filter(Boolean),

  // Outbound goes through the self-hosted Postfix relay (untrobotics-mail),
  // same as the website. SendGrid is inbound-ingest only — not used here.
  smtpHost: env('SMTP_HOST', 'mail.untrobotics-mail.svc.cluster.local'),
  smtpPort: intEnv('SMTP_PORT', 25),
  emailFrom: env('EMAIL_FROM', 'verify@untrobotics.com'),
  emailFromName: env('EMAIL_FROM_NAME', 'UNT Robotics'),

  // --- Database -------------------------------------------------------------
  db: {
    host: envAny(['DB_HOST', 'DATABASE_HOST'], 'mysql'),
    user: envAny(['DB_USER', 'DATABASE_USER'], 'untrobotics-web'),
    password: envAny(['DB_PASSWORD', 'DATABASE_PASSWORD']),
    database: envAny(['DB_NAME', 'DATABASE_NAME'], 'untrobotics'),
  },

  // --- Verification tunables ------------------------------------------------
  // NOTE on brute-force resistance (see README for the full write-up):
  //   With CODE_LENGTH=6 there are 1,000,000 codes; MAX_TOKEN_ATTEMPTS=5 per
  //   code means a single code survives ~0.0005% guess odds. Codes also expire
  //   (CODE_TTL_SECONDS), each /verify is rate-limited (VERIFY_COOLDOWN_SECONDS
  //   + MAX_VERIFY_PER_HOUR), each /token attempt has a cooldown
  //   (TOKEN_ATTEMPT_COOLDOWN_SECONDS) and a global LOCKOUT_THRESHOLD trips a
  //   LOCKOUT_SECONDS freeze. Together these make online guessing impractical.
  //   4-digit codes are materially weaker — see README — default stays at 6.
  codeLength: intEnv('CODE_LENGTH', 6),
  codeTtlSeconds: intEnv('CODE_TTL_SECONDS', 600),
  maxVerifyPerHour: intEnv('MAX_VERIFY_PER_HOUR', 5),
  verifyCooldownSeconds: intEnv('VERIFY_COOLDOWN_SECONDS', 60),
  maxTokenAttempts: intEnv('MAX_TOKEN_ATTEMPTS', 5),
  tokenAttemptCooldownSeconds: intEnv('TOKEN_ATTEMPT_COOLDOWN_SECONDS', 5),
  lockoutThreshold: intEnv('LOCKOUT_THRESHOLD', 10),
  lockoutSeconds: intEnv('LOCKOUT_SECONDS', 3600),

  // Optional secret mixed into the code hash so a DB leak alone can't be used
  // to recompute hashes offline. Falls back to HASH_SALT (already in the app
  // secret set) and finally to a constant — set one in prod.
  hashSecret: envAny(['VERIFY_HASH_SECRET', 'HASH_SALT'], 'untrobotics-verify'),
});

/**
 * Validate required config. Throws with a single combined message listing every
 * missing key so the operator fixes them all in one pass.
 */
function assertConfig() {
  const missing = [];
  if (!config.token) missing.push('DISCORD_BOT_TOKEN (or DISCORD_ADMIN_BOT_TOKEN)');
  if (!config.clientId) missing.push('DISCORD_CLIENT_ID (or DISCORD_APP_CLIENT_ID)');
  if (!config.guildId) missing.push('DISCORD_GUILD_ID');
  if (!config.verifiedRoleId) missing.push('DISCORD_VERIFIED_ROLE_ID');
  if (!config.verifyChannelId) missing.push('DISCORD_VERIFY_CHANNEL_ID');
  if (!config.db.password) missing.push('DB_PASSWORD (or DATABASE_PASSWORD)');
  if (!(config.codeLength >= 4 && config.codeLength <= 10)) {
    missing.push('CODE_LENGTH (must be between 4 and 10)');
  }
  if (missing.length) {
    throw new Error(
      'Missing/invalid required configuration:\n  - ' + missing.join('\n  - ')
    );
  }
}

module.exports = { config, assertConfig };
