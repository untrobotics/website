'use strict';

/**
 * One-time backfill: grant a role (default "Verified Legacy") to EVERY current
 * human member of the guild. Idempotent — members who already have the role are
 * skipped, so it is safe to re-run.
 *
 * Usage (role id via env or first arg):
 *   DISCORD_VERIFIED_LEGACY_ROLE_ID=1521695260521005147 node src/scripts/backfill-legacy.js
 *   node src/scripts/backfill-legacy.js 1521695260521005147
 *
 * Requires the Server Members (GuildMembers) intent and the bot's role to sit
 * ABOVE the target role in the hierarchy. discord.js queues the role edits and
 * respects Discord's rate limits automatically, so large servers just take a
 * little while.
 */

const { Client, GatewayIntentBits } = require('discord.js');
const { config } = require('../config');
const log = require('../logger');

const roleId = process.env.DISCORD_VERIFIED_LEGACY_ROLE_ID || process.argv[2];
if (!roleId) {
  log.error('backfill-legacy: no role id (set DISCORD_VERIFIED_LEGACY_ROLE_ID or pass as arg)');
  process.exit(1);
}
if (!config.token) {
  log.error('backfill-legacy: no bot token configured');
  process.exit(1);
}

const client = new Client({
  intents: [GatewayIntentBits.Guilds, GatewayIntentBits.GuildMembers],
});

client.once('ready', async () => {
  let code = 0;
  try {
    const guild = await client.guilds.fetch(config.guildId);
    const role = await guild.roles.fetch(roleId);
    if (!role) throw new Error(`role ${roleId} not found in guild ${config.guildId}`);

    // Guard: the bot can only assign roles below its own highest role.
    const me = await guild.members.fetchMe();
    if (role.position >= me.roles.highest.position) {
      throw new Error(
        `bot's highest role must be ABOVE "${role.name}" in the hierarchy — move it up and re-run`
      );
    }

    const members = await guild.members.fetch(); // needs Server Members intent
    log.info(`backfill-legacy: ${members.size} members; granting "${role.name}" (${roleId})`);

    let added = 0;
    let skipped = 0;
    let failed = 0;
    for (const member of members.values()) {
      if (member.user.bot) { skipped++; continue; }
      if (member.roles.cache.has(roleId)) { skipped++; continue; }
      try {
        await member.roles.add(roleId, 'Verified Legacy one-time backfill');
        added++;
        if (added % 25 === 0) log.info(`  ...${added} granted so far`);
      } catch (err) {
        failed++;
        log.error(`  failed for ${member.user.tag} (${member.id}): ${err.message}`);
      }
    }

    log.info(`backfill-legacy DONE: added=${added} skipped=${skipped} failed=${failed}`);
    if (failed > 0) code = 2;
  } catch (err) {
    log.error(`backfill-legacy error: ${err.message}`);
    code = 1;
  } finally {
    client.destroy();
    process.exit(code);
  }
});

client.on('error', (e) => log.error(`client error: ${e.message}`));
client.login(config.token);
