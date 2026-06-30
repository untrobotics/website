'use strict';

const { Events, ActivityType } = require('discord.js');
const log = require('../logger');

module.exports = {
  name: Events.ClientReady,
  once: true,
  execute(client) {
    log.info(`ready: logged in as ${client.user.tag} (${client.user.id})`);
    log.info(`ready: serving ${client.guilds.cache.size} guild(s)`);
    try {
      client.user.setActivity('/verify to join', { type: ActivityType.Watching });
    } catch (_) {
      /* presence is best-effort */
    }
  },
};
