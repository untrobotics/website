'use strict';

const { SlashCommandBuilder } = require('discord.js');
const { config } = require('../config');
const verification = require('../lib/verification');
const log = require('../logger');

function fmtDuration(seconds) {
  if (seconds == null) return 'a moment';
  if (seconds < 60) return `${seconds}s`;
  const m = Math.ceil(seconds / 60);
  return `${m} minute${m === 1 ? '' : 's'}`;
}

module.exports = {
  data: new SlashCommandBuilder()
    .setName('verify')
    .setDescription('Verify your UNT email to get the Verified UNT role.')
    .addStringOption((opt) =>
      opt
        .setName('email')
        .setDescription('Your UNT email (e.g. yourEUID@unt.edu)')
        .setRequired(true)
        .setMaxLength(254)
    ),

  async execute(interaction) {
    // All replies ephemeral so the email is never shown in-channel.
    await interaction.deferReply({ ephemeral: true });

    // Channel gate.
    if (interaction.channelId !== config.verifyChannelId) {
      await interaction.editReply(
        `Please use this command in <#${config.verifyChannelId}>.`
      );
      return;
    }

    // Already has the role? Short-circuit.
    if (
      interaction.member &&
      interaction.member.roles &&
      interaction.member.roles.cache.has(config.verifiedRoleId)
    ) {
      await interaction.editReply("You're already verified. 🎉");
      return;
    }

    const email = interaction.options.getString('email');

    let result;
    try {
      result = await verification.requestCode(interaction.user.id, email);
    } catch (err) {
      log.error('verify: requestCode threw', err.message);
      await interaction.editReply(
        'Something went wrong on our end. Please try again in a minute.'
      );
      return;
    }

    if (result.ok) {
      await interaction.editReply(
        `Check your inbox — we emailed a ${config.codeLength}-digit code to ` +
          `**${result.email}**. It expires in ${fmtDuration(config.codeTtlSeconds)}.\n` +
          'Enter it here with `/token <code>`. (Check spam if you don\'t see it.)'
      );
      return;
    }

    const messages = {
      invalid_email: 'That doesn\'t look like a valid email address. Please try again.',
      bad_domain:
        'That email isn\'t a recognised UNT address. Allowed domains: ' +
        config.allowedEmailDomains.map((d) => `\`${d}\``).join(', ') +
        '.',
      already_verified: "You're already verified. 🎉",
      email_taken:
        'That email is already linked to a different Discord account. ' +
        'If this is you, contact an officer for help.',
      cooldown: `Please wait ${fmtDuration(result.retryAfter)} before requesting another code.`,
      hourly_limit:
        `You've requested too many codes recently. Try again in ` +
        `${fmtDuration(result.retryAfter)}.`,
      email_failed:
        'We couldn\'t send the email right now. Please try again in a minute.',
    };

    await interaction.editReply(messages[result.reason] || 'Unable to start verification.');
  },
};
