'use strict';

const { Events, GuildScheduledEventStatus } = require('discord.js');
const { config } = require('../config');
const log = require('../logger');

/**
 * Announce an event when it starts. Discord flips a scheduled event to ACTIVE at
 * its start time, firing this update; we post to the announcements channel on the
 * SCHEDULED -> ACTIVE transition only. (Requires the GuildScheduledEvents intent.)
 */
module.exports = {
  name: Events.GuildScheduledEventUpdate,
  once: false,
  async execute(oldEvent, newEvent) {
    try {
      if (!newEvent || newEvent.guildId !== config.guildId) return;

      const wasScheduled = !oldEvent || oldEvent.status === GuildScheduledEventStatus.Scheduled;
      const nowActive = newEvent.status === GuildScheduledEventStatus.Active;
      if (!(wasScheduled && nowActive)) return;

      const chId = config.announcementsChannelId;
      if (!chId) return;
      const channel = await newEvent.client.channels.fetch(chId).catch(() => null);
      if (!channel || !channel.isTextBased()) return;

      const location = newEvent.entityMetadata && newEvent.entityMetadata.location;
      const url = `https://discord.com/events/${newEvent.guildId}/${newEvent.id}`;

      let msg = `📢 **${newEvent.name}** is starting now!`;
      if (location) msg += `\n📍 ${location}`;
      if (newEvent.description) msg += `\n\n${newEvent.description}`;
      msg += `\n\n🔗 ${url}`;

      await channel.send({ content: msg, allowedMentions: { parse: [] } });
      log.info('guildScheduledEventUpdate: announced start of', newEvent.name);
    } catch (err) {
      log.warn('guildScheduledEventUpdate: announce failed', err.message);
    }
  },
};
