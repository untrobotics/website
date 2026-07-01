'use strict';

/**
 * One-time backfill: grant a role (default "Verified Legacy") to EVERY current
 * human member of the guild. Idempotent — members who already have the role are
 * skipped, so it is safe to re-run.
 *
 * Uses the REST API only (NO gateway connection), so it can run alongside the
 * live bot without disrupting its gateway session. @discordjs/rest queues the
 * requests and respects Discord's rate limits automatically.
 *
 * Usage (role id via env or first arg):
 *   DISCORD_VERIFIED_LEGACY_ROLE_ID=1521695260521005147 node src/scripts/backfill-legacy.js
 *   node src/scripts/backfill-legacy.js 1521695260521005147
 *
 * Requires the Server Members intent (for the list-members REST endpoint) and
 * the bot's role to sit ABOVE the target role in the hierarchy.
 */

const { REST, Routes } = require('discord.js');
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

const rest = new REST({ version: '10' }).setToken(config.token);

async function fetchAllMembers() {
  const members = [];
  let after = '0';
  // Paginate: max 1000 per page, page by ascending user id.
  // eslint-disable-next-line no-constant-condition
  while (true) {
    const page = await rest.get(Routes.guildMembers(config.guildId), {
      query: new URLSearchParams({ limit: '1000', after }),
    });
    if (!Array.isArray(page) || page.length === 0) break;
    members.push(...page);
    after = page[page.length - 1].user.id;
    if (page.length < 1000) break;
  }
  return members;
}

async function main() {
  const members = await fetchAllMembers();
  log.info(`backfill-legacy: ${members.length} members; granting role ${roleId}`);

  let added = 0;
  let skipped = 0;
  let failed = 0;
  for (const m of members) {
    if (!m.user || m.user.bot) { skipped++; continue; }
    if (Array.isArray(m.roles) && m.roles.includes(roleId)) { skipped++; continue; }
    try {
      await rest.put(Routes.guildMemberRole(config.guildId, m.user.id, roleId), {
        reason: 'Verified Legacy one-time backfill',
      });
      added++;
      if (added % 25 === 0) log.info(`  ...${added} granted so far`);
    } catch (err) {
      failed++;
      const who = m.user ? `${m.user.username} (${m.user.id})` : 'unknown';
      log.error(`  failed for ${who}: ${err.message}`);
      // If the very first grant fails with 403, it's almost certainly the role
      // hierarchy (bot role must be above the target) — bail early with a hint.
      if (added === 0 && failed === 1 && /Missing Permissions|403/.test(err.message)) {
        log.error('  -> looks like a permissions/hierarchy issue: move the bot\'s role ABOVE the target role and re-run.');
        break;
      }
    }
  }

  log.info(`backfill-legacy DONE: added=${added} skipped=${skipped} failed=${failed}`);
  return failed > 0 ? 2 : 0;
}

main()
  .then((code) => process.exit(code))
  .catch((err) => {
    log.error(`backfill-legacy error: ${err.message}`);
    process.exit(1);
  });
