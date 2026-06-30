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

const GENERIC_INVALID =
  'That code is invalid or has expired. Double-check it, or run `/verify` to get a new one.';

async function postAudit(client, content) {
  if (!config.logChannelId) return;
  try {
    const channel = await client.channels.fetch(config.logChannelId);
    if (channel && channel.isTextBased()) await channel.send(content);
  } catch (err) {
    log.warn('token: could not post audit line', err.message);
  }
}

module.exports = {
  data: new SlashCommandBuilder()
    .setName('token')
    .setDescription('Enter the code we emailed you to finish verification.')
    .addStringOption((opt) =>
      opt
        .setName('code')
        .setDescription('The numeric code from your email')
        .setRequired(true)
        .setMaxLength(16)
    ),

  async execute(interaction) {
    await interaction.deferReply({ ephemeral: true });

    if (interaction.channelId !== config.verifyChannelId) {
      await interaction.editReply(
        `Please use this command in <#${config.verifyChannelId}>.`
      );
      return;
    }

    const code = interaction.options.getString('code');

    let result;
    try {
      result = await verification.redeemToken(interaction.user.id, code);
    } catch (err) {
      log.error('token: redeemToken threw', err.message);
      await interaction.editReply(
        'Something went wrong on our end. Please try again in a minute.'
      );
      return;
    }

    if (result.ok) {
      // Grant the Verified UNT role.
      try {
        const member =
          interaction.member ??
          (await interaction.guild.members.fetch(interaction.user.id));
        await member.roles.add(config.verifiedRoleId, 'UNT email verification passed');
      } catch (err) {
        // Verified in DB but couldn't assign the role — almost always a role
        // hierarchy / Manage Roles permission problem. Tell the user to ping an
        // officer and log loudly.
        log.error('token: role assignment failed', err.message);
        await interaction.editReply(
          'Your email is verified, but I couldn\'t assign the role (a permissions ' +
            'issue on my end). Please ping an officer.'
        );
        await postAudit(
          interaction.client,
          `:warning: Verification OK for <@${interaction.user.id}> (${result.email}) ` +
            `but **role assignment FAILED**: ${err.message}`
        );
        return;
      }

      await interaction.editReply('You\'re verified! Welcome to UNT Robotics. 🤖✅');
      await postAudit(
        interaction.client,
        `:white_check_mark: Verified <@${interaction.user.id}> ` +
          `(\`${interaction.user.tag}\`, id ${interaction.user.id}) → ${result.email}`
      );
      return;
    }

    // Failure paths.
    let message;
    switch (result.reason) {
      case 'no_request':
        message = 'You haven\'t requested a code yet. Run `/verify <your UNT email>` first.';
        break;
      case 'already_verified':
        message = 'You\'re already verified. 🎉';
        break;
      case 'locked':
        message =
          `Too many failed attempts. Verification is locked for ` +
          `${fmtDuration(result.retryAfter)}. Try again later.`;
        await postAudit(
          interaction.client,
          `:lock: <@${interaction.user.id}> (${interaction.user.tag}) hit the ` +
            `verification lockout threshold.`
        );
        break;
      case 'attempt_cooldown':
        message = `Slow down — try again in ${fmtDuration(result.retryAfter)}.`;
        break;
      case 'expired':
      case 'invalid':
      default:
        message = GENERIC_INVALID;
        if (result.locked) {
          message =
            `Too many failed attempts — verification is now locked for ` +
            `${fmtDuration(result.retryAfter)}.`;
        } else if (result.burned) {
          message =
            'That code is invalid, and you\'ve used all attempts for it. ' +
            'Run `/verify` to get a new code.';
        } else if (typeof result.attemptsLeft === 'number') {
          message = `${GENERIC_INVALID} (${result.attemptsLeft} attempt(s) left.)`;
        }
        break;
    }

    await interaction.editReply(message);
  },
};
