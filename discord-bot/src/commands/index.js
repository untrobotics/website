'use strict';

const fs = require('fs');
const path = require('path');

/**
 * Auto-load every command module in this directory (except this loader).
 * A command module exports { data: SlashCommandBuilder, execute(interaction) }.
 *
 * @returns {Map<string, object>} name -> command module
 */
function loadCommands() {
  const commands = new Map();
  const files = fs
    .readdirSync(__dirname)
    .filter((f) => f.endsWith('.js') && f !== 'index.js');

  for (const file of files) {
    // eslint-disable-next-line global-require, import/no-dynamic-require
    const command = require(path.join(__dirname, file));
    if (!command.data || typeof command.execute !== 'function') {
      throw new Error(`Command file ${file} is missing "data" or "execute"`);
    }
    commands.set(command.data.name, command);
  }
  return commands;
}

module.exports = { loadCommands };
