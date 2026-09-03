'use strict';

/**
 * Register (or refresh) guild slash commands. Run with `npm run register`.
 *
 * Guild commands update instantly (global commands can take up to an hour), and
 * this bot only serves one guild, so we register to DISCORD_GUILD_ID.
 *
 * This is a one-shot CLI script: it does NOT open a gateway connection.
 */

const { REST, Routes } = require('discord.js');
const { config, assertConfig } = require('../config');
const { loadCommands } = require('../commands');
const log = require('../logger');

async function main() {
  // Only the identity bits are needed to register; don't require DB/SendGrid.
  if (!config.token) throw new Error('DISCORD_BOT_TOKEN (or DISCORD_ADMIN_BOT_TOKEN) is required');
  if (!config.clientId) throw new Error('DISCORD_CLIENT_ID (or DISCORD_APP_CLIENT_ID) is required');
  if (!config.guildId) throw new Error('DISCORD_GUILD_ID is required');

  const commands = loadCommands();
  const body = [...commands.values()].map((c) => c.data.toJSON());

  const rest = new REST({ version: '10' }).setToken(config.token);

  log.info(`register: pushing ${body.length} command(s) to guild ${config.guildId}…`);
  const data = await rest.put(
    Routes.applicationGuildCommands(config.clientId, config.guildId),
    { body }
  );
  log.info(`register: done – ${data.length} command(s) registered:`, body.map((c) => c.name).join(', '));
}

// Quiet the unused-var lint for assertConfig (intentionally not used here).
void assertConfig;

main().catch((err) => {
  log.error('register: failed', err.stack || err.message);
  process.exit(1);
});
