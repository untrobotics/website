'use strict';

/**
 * In-memory, per-attempt cooldown guard. The authoritative verification rate
 * limits (per-hour send window, lockouts) live in the database row so they
 * survive restarts; this module only enforces the short, high-frequency
 * "wait a few seconds between attempts" cooldowns where a DB column would be
 * overkill. Safe because the bot is a single gateway instance (replicas: 1).
 */

// key -> epoch-ms when the next action is allowed.
const nextAllowedAt = new Map();

/**
 * @returns {number} seconds remaining before `key` may act again (0 if ready).
 */
function secondsRemaining(key) {
  const at = nextAllowedAt.get(key);
  if (!at) return 0;
  const remaining = Math.ceil((at - Date.now()) / 1000);
  return remaining > 0 ? remaining : 0;
}

/** Arm a cooldown of `seconds` for `key`. */
function arm(key, seconds) {
  nextAllowedAt.set(key, Date.now() + seconds * 1000);
}

/** Clear a cooldown early (e.g. on success). */
function clear(key) {
  nextAllowedAt.delete(key);
}

// Periodically drop expired entries so the map can't grow unbounded.
const sweep = setInterval(() => {
  const now = Date.now();
  for (const [k, at] of nextAllowedAt) if (at <= now) nextAllowedAt.delete(k);
}, 60 * 1000);
// Don't keep the event loop alive just for the sweeper.
if (sweep.unref) sweep.unref();

module.exports = { secondsRemaining, arm, clear };
