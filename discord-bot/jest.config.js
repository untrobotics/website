'use strict';

/** Jest config for the bot's pure-logic unit tests (no Discord/DB needed). */
module.exports = {
  testEnvironment: 'node',
  testMatch: ['**/test/**/*.test.js'],
  // The prod image is built with --omit=dev, so tests never ship; they run in CI.
};
