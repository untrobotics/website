'use strict';

const { Events } = require('discord.js');
const { matchKeyword, reactionsForChannel } = require('../lib/autoresponses');
const { handleSmsReply } = require('../lib/sms');
const log = require('../logger');

module.exports = {
  name: Events.MessageCreate,
  once: false,
  async execute(message) {
    // Ignore bots (and our own messages) to avoid loops.
    if (message.author.bot) return;
    if (message.client.user && message.author.id === message.client.user.id) return;
    // Only handle guild messages.
    if (!message.guild) return;

    // --- SMS reply: reply to an incoming-SMS embed -> text the sender back ---
    try {
      if (await handleSmsReply(message)) return;
    } catch (err) {
      log.warn('messageCreate: sms reply handler failed', err.message);
    }

    // --- Auto-reactions (configurable channel -> emoji) ---------------------
    const emojis = reactionsForChannel(message.channelId);
    for (const emoji of emojis) {
      try {
        await message.react(emoji);
      } catch (err) {
        log.warn('messageCreate: react failed', emoji, err.message);
      }
    }

    // --- Keyword auto-responses --------------------------------------------
    if (message.content) {
      const reply = matchKeyword(message.content);
      if (reply) {
        try {
          await message.reply({ content: reply, allowedMentions: { repliedUser: false } });
        } catch (err) {
          log.warn('messageCreate: auto-reply failed', err.message);
        }
      }
    }
  },
};
