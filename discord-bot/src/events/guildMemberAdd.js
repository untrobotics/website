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

    // Prefer a DM; fall back to the verify channel if DMs are closed.
    try {
      await member.send(welcome);
      log.info('guildMemberAdd: DMed welcome to', member.user.tag);
    } catch (_) {
      try {
        const channel = await member.guild.channels.fetch(config.verifyChannelId);
        if (channel && channel.isTextBased()) await channel.send(welcome);
        log.info('guildMemberAdd: posted welcome in verify channel for', member.user.tag);
      } catch (err) {
        log.warn('guildMemberAdd: could not welcome', member.user.tag, err.message);
      }
    }
  },
};
