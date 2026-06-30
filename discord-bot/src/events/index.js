'use strict';

const fs = require('fs');
const path = require('path');

/**
 * Auto-load every event module in this directory (except this loader) and wire
 * it to the client. An event module exports { name, once?, execute(...args) }.
 */
function registerEvents(client) {
  const files = fs
    .readdirSync(__dirname)
    .filter((f) => f.endsWith('.js') && f !== 'index.js');

  for (const file of files) {
    // eslint-disable-next-line global-require, import/no-dynamic-require
    const event = require(path.join(__dirname, file));
    if (!event.name || typeof event.execute !== 'function') {
      throw new Error(`Event file ${file} is missing "name" or "execute"`);
    }
    if (event.once) {
      client.once(event.name, (...args) => event.execute(...args));
    } else {
      client.on(event.name, (...args) => event.execute(...args));
    }
  }
}

module.exports = { registerEvents };
