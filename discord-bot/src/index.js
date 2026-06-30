'use strict';

const fs = require('fs');
const { Client, GatewayIntentBits, Partials, Collection } = require('discord.js');

const { config, assertConfig } = require('./config');
const log = require('./logger');
const db = require('./db');
const { loadCommands } = require('./commands');
const { registerEvents } = require('./events');

// Heartbeat file the k8s liveness probe `exec`s on (see k8s/base/discord-bot.yaml).
// We touch it on every gateway heartbeat ack so a wedged connection is detected.
const HEARTBEAT_FILE = process.env.HEARTBEAT_FILE || '/tmp/discord-bot-heartbeat';

function touchHeartbeat() {
  try {
    fs.writeFileSync(HEARTBEAT_FILE, String(Date.now()));
  } catch (_) {
    /* best-effort */
  }
}

async function main() {
  // Fail fast on bad config before opening any connections.
  assertConfig();

  // Confirm DB reachability up front (loud crash-loop beats silent failure).
  await db.ping();

  const client = new Client({
    intents: [
      GatewayIntentBits.Guilds,
      GatewayIntentBits.GuildMessages,
      GatewayIntentBits.MessageContent, // privileged — enable in Dev Portal
      GatewayIntentBits.GuildMembers, // privileged — enable in Dev Portal
      GatewayIntentBits.GuildMessageReactions,
    ],
    // Needed so guildMemberAdd / reactions fire reliably even for uncached objects.
    partials: [Partials.GuildMember, Partials.Message, Partials.Channel, Partials.Reaction],
  });

  // Load and attach slash commands for the interaction router.
  client.commands = new Collection();
  for (const [name, command] of loadCommands()) client.commands.set(name, command);
  log.info('startup: loaded commands —', [...client.commands.keys()].join(', '));

  // Wire all event handlers (ready, guildMemberAdd, messageCreate, interactionCreate).
  registerEvents(client);

  // Heartbeat: refresh the liveness file on each gateway ack and right away.
  touchHeartbeat();
  client.on('clientReady', touchHeartbeat);
  // discord.js v14: 'ready' is emitted; keep both for forward-compat.
  client.on('ready', touchHeartbeat);
  const hb = setInterval(touchHeartbeat, 30 * 1000);
  if (hb.unref) hb.unref();

  // Surface gateway errors instead of swallowing them.
  client.on('error', (err) => log.error('client error', err.message));
  client.on('shardError', (err) => log.error('shard error', err.message));
  client.on('warn', (msg) => log.warn('client warn', msg));

  // --- Graceful shutdown ----------------------------------------------------
  let shuttingDown = false;
  async function shutdown(signal) {
    if (shuttingDown) return;
    shuttingDown = true;
    log.info(`shutdown: received ${signal}, closing…`);
    try {
      client.destroy();
    } catch (err) {
      log.warn('shutdown: client.destroy failed', err.message);
    }
    try {
      await db.pool.end();
    } catch (err) {
      log.warn('shutdown: db pool end failed', err.message);
    }
    try {
      fs.unlinkSync(HEARTBEAT_FILE);
    } catch (_) {
      /* ignore */
    }
    process.exit(0);
  }
  process.on('SIGINT', () => shutdown('SIGINT'));
  process.on('SIGTERM', () => shutdown('SIGTERM'));

  process.on('unhandledRejection', (reason) => {
    log.error('unhandledRejection', reason && reason.stack ? reason.stack : reason);
  });
  process.on('uncaughtException', (err) => {
    log.error('uncaughtException', err.stack || err.message);
    // Let the process exit so k8s restarts a clean instance.
    shutdown('uncaughtException');
  });

  log.info('startup: logging in to the Discord gateway…');
  await client.login(config.token);
}

main().catch((err) => {
  log.error('fatal: startup failed', err.stack || err.message);
  process.exit(1);
});
