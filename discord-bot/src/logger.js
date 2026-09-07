'use strict';

/**
 * Tiny structured-ish logger. Keeps log lines greppable and timestamps in UTC.
 * Dependency-free. ERROR-level lines are also forwarded to the Discord log
 * channel (rate-limited) so prod issues are visible without shelling into the
 * pod – mirroring the website's Apache log-forwarder.
 */

function ts() {
  return new Date().toISOString();
}

function fmt(level, args) {
  return [`[${ts()}]`, `[${level}]`, ...args];
}

// Best-effort, rate-limited forward of error lines to the log channel via REST.
let lastForward = 0;
const FORWARD_INTERVAL_MS = 60 * 1000; // at most one forwarded error per minute
function forwardError(line) {
  const now = Date.now();
  if (now - lastForward < FORWARD_INTERVAL_MS) return;
  lastForward = now;
  let config;
  try {
    ({ config } = require('./config')); // lazy to avoid load-order issues
  } catch (_) {
    return;
  }
  if (!config.logChannelId || !config.token) return;
  try {
    fetch(`https://discord.com/api/v10/channels/${config.logChannelId}/messages`, {
      method: 'POST',
      headers: { Authorization: 'Bot ' + config.token, 'Content-Type': 'application/json' },
      body: JSON.stringify({ content: ('\u{1F916} bot error: ' + line).slice(0, 1900) }),
    }).catch(() => {});
  } catch (_) {
    /* never let logging throw */
  }
}

module.exports = {
  info: (...args) => console.log(...fmt('INFO', args)),
  warn: (...args) => console.warn(...fmt('WARN', args)),
  error: (...args) => {
    console.error(...fmt('ERROR', args));
    try {
      forwardError(args.map((a) => (a instanceof Error ? a.message : String(a))).join(' '));
    } catch (_) {
      /* ignore */
    }
  },
  debug: (...args) => {
    if (process.env.DEBUG) console.log(...fmt('DEBUG', args));
  },
};
