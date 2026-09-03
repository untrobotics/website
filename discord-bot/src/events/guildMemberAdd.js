'use strict';

const { Events } = require('discord.js');
const { config } = require('../config');
const log = require('../logger');

// Remember the last welcome we posted per guild so we can delete it before
// posting the next one. Otherwise welcomes pile up in #verify-here and bury the
// pinned verification instructions, so new members see a wall of pings instead
// of how to verify (URW-221). The instructions also live in the channel topic
// as an always-visible anchor.
const lastWelcome = new Map();

module.exports = {
  name: Events.GuildMemberAdd,
  once: false,
  async execute(member) {
    // Only react for our guild.
    if (member.guild.id !== config.guildId) return;

    const helpMention = config.verifyHelpChannelId
      ? `<#${config.verifyHelpChannelId}>`
      : '#verification-help';
    const welcome =
      `Welcome to **UNT Robotics**, ${member}! 🤖\n` +
      `The server stays locked until you verify — it only takes a minute:\n` +
      `**1.** Type \`/verify\` here and **click the \`/verify\` popup** that appears above the box (don't just send it as a message).\n` +
      `**2.** Enter your **@unt.edu / @my.unt.edu** email in the box — it stays private, so don't type it in chat.\n` +
      `**3.** I'll email you a code; enter it with \`/token\` the same way.\n` +
      `Not a UNT student — from another school, high school, or an industry mentor? Ask for **manual verification** in ${helpMention}.`;

    // Post the welcome publicly in the verify channel (pinging the new member so
    // they get notified). Posting in-channel — rather than DMing — means the
    // prompt is visible and works even for members who have DMs closed.
    try {
      const channel = await member.guild.channels.fetch(config.verifyChannelId);
      if (!channel || !channel.isTextBased()) {
        log.warn('guildMemberAdd: verify channel unavailable; could not welcome', member.user.tag);
        return;
      }

      // Self-replacing: remove the previous welcome so only the newest remains.
      const prevId = lastWelcome.get(member.guild.id);
      if (prevId) {
        await channel.messages.delete(prevId).catch(() => {});
      }

      const sent = await channel.send({
        content: welcome,
        allowedMentions: { users: [member.id] },
      });
      lastWelcome.set(member.guild.id, sent.id);
      log.info('guildMemberAdd: welcomed', member.user.tag, 'in verify channel');
    } catch (err) {
      log.warn('guildMemberAdd: could not welcome', member.user.tag, err.message);
    }
  },
};
