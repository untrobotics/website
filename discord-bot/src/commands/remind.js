'use strict';

const { SlashCommandBuilder, MessageFlags } = require('discord.js');
const { parseDuration, humanDuration, createReminder } = require('../lib/reminders');
const log = require('../logger');

// Guard rails.
const MIN_FIRST = 30;              // 30 seconds
const MAX_FIRST = 400 * 86400;     // ~400 days out
const MIN_REPEAT = 300;            // clamp repeats to >= 5 min so we don't spam

module.exports = {
  data: new SlashCommandBuilder()
    .setName('remind')
    .setDescription('Set a reminder in this channel — optionally repeating until it’s marked done.')
    .addStringOption((o) =>
      o.setName('text').setDescription('What to remind about').setRequired(true).setMaxLength(500))
    .addStringOption((o) =>
      o.setName('in').setDescription('When to first remind, e.g. 30m, 2h, 1d, 1h30m').setRequired(true).setMaxLength(40))
    .addStringOption((o) =>
      o.setName('repeat').setDescription('Keep reminding at this interval until marked done, e.g. 1h, 1d (optional)').setRequired(false).setMaxLength(40))
    .addMentionableOption((o) =>
      o.setName('who').setDescription('Who to remind — a person or role (defaults to you)').setRequired(false)),

  async execute(interaction) {
    if (!interaction.inGuild()) {
      await interaction.reply({ content: 'Reminders can only be set inside a server channel.', flags: MessageFlags.Ephemeral });
      return;
    }

    const text = interaction.options.getString('text').trim();
    const inRaw = interaction.options.getString('in');
    const repeatRaw = interaction.options.getString('repeat');

    const inSeconds = parseDuration(inRaw);
    if (!inSeconds || inSeconds < MIN_FIRST) {
      await interaction.reply({
        content: `I couldn’t read a time from “${inRaw}”. Try \`30m\`, \`2h\`, \`1d\`, or \`1h30m\` (minimum 30 seconds).`,
        flags: MessageFlags.Ephemeral,
      });
      return;
    }
    if (inSeconds > MAX_FIRST) {
      await interaction.reply({ content: 'That’s too far out — keep it under about a year, please.', flags: MessageFlags.Ephemeral });
      return;
    }

    let repeatSeconds = null;
    if (repeatRaw && repeatRaw.trim() && !/^(off|none|no)$/i.test(repeatRaw.trim())) {
      repeatSeconds = parseDuration(repeatRaw);
      if (!repeatSeconds) {
        await interaction.reply({
          content: `I couldn’t read the repeat interval “${repeatRaw}”. Try \`1h\`, \`6h\`, or \`1d\` — or leave it blank for a one-off.`,
          flags: MessageFlags.Ephemeral,
        });
        return;
      }
      if (repeatSeconds < MIN_REPEAT) {
        repeatSeconds = MIN_REPEAT; // don't let anyone set a 10-second nag loop
      }
    }

    // Target: a member/user or a role. Defaults to the invoker.
    let targetType = 'user';
    let targetId = interaction.user.id;
    const who = interaction.options.getMentionable('who');
    if (who) {
      if (who.user) {
        targetId = who.user.id;          // GuildMember
        targetType = 'user';
      } else if (who.username) {
        targetId = who.id;               // raw User
        targetType = 'user';
      } else {
        targetId = who.id;               // Role
        targetType = 'role';
      }
    }

    let id;
    try {
      id = await createReminder({
        guildId: interaction.guildId,
        channelId: interaction.channelId,
        creatorId: interaction.user.id,
        targetType,
        targetId,
        body: text,
        inSeconds,
        repeatSeconds,
      });
    } catch (err) {
      log.error('remind: failed to save reminder', err.message);
      await interaction.reply({ content: 'Sorry, I couldn’t save that reminder. Try again in a moment.', flags: MessageFlags.Ephemeral });
      return;
    }

    const mention = targetType === 'role' ? `<@&${targetId}>` : `<@${targetId}>`;
    const repeatLine = repeatSeconds ? `, then every **${humanDuration(repeatSeconds)}** until someone marks it done` : '';
    await interaction.reply({
      content: `⏰ Got it — I’ll remind ${mention} in **${humanDuration(inSeconds)}**${repeatLine}.\n> ${text}`,
      flags: MessageFlags.Ephemeral,
      allowedMentions: { parse: [] },
    });
    log.info(`remind: #${id} set by ${interaction.user.tag} (in ${inSeconds}s, repeat ${repeatSeconds || 'none'})`);
  },
};
