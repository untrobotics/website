'use strict';

const { Events } = require('discord.js');
const { config } = require('../config');
const log = require('../logger');

module.exports = {
  name: Events.GuildMemberAdd,
  once: false,
  async execute(member) {
    // Only react for our guild.
    if (member.guild.id !== config.guildId) return;

    const welcome =
      `Welcome to **UNT Robotics**, ${member}! 🤖\n` +
      `To unlock the rest of the server, verify your UNT email in ` +
      `<#${config.verifyChannelId}> with \`/verify <your UNT email>\`.`;

    // Post the welcome publicly in the verify channel (pinging the new member so
    // they get notified). Posting in-channel — rather than DMing — means the
    // prompt is visible and works even for members who have DMs closed.
    try {
      const channel = await member.guild.channels.fetch(config.verifyChannelId);
      if (channel && channel.isTextBased()) {
        await channel.send({ content: welcome, allowedMentions: { users: [member.id] } });
        log.info('guildMemberAdd: welcomed', member.user.tag, 'in verify channel');
      } else {
        log.warn('guildMemberAdd: verify channel unavailable; could not welcome', member.user.tag);
      }
    } catch (err) {
      log.warn('guildMemberAdd: could not welcome', member.user.tag, err.message);
    }
  },
};
