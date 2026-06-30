'use strict';

/**
 * Tiny structured-ish logger. Keeps log lines greppable and timestamps in UTC.
 * Deliberately dependency-free.
 */

function ts() {
  return new Date().toISOString();
}

function fmt(level, args) {
  return [`[${ts()}]`, `[${level}]`, ...args];
}

module.exports = {
  info: (...args) => console.log(...fmt('INFO', args)),
  warn: (...args) => console.warn(...fmt('WARN', args)),
  error: (...args) => console.error(...fmt('ERROR', args)),
  debug: (...args) => {
    if (process.env.DEBUG) console.log(...fmt('DEBUG', args));
  },
};
