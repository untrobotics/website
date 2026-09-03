'use strict';

const {
  ActionRowBuilder,
  StringSelectMenuBuilder,
  ButtonBuilder,
  ButtonStyle,
  MessageFlags,
} = require('discord.js');
const { config } = require('../config');
const log = require('../logger');

// customIds routed from events/interactionCreate.js
const OPEN_ID = 'selfroles_open';
const SELECT_ID = 'selfroles_select';

function isVerified(member) {
  if (!member || !member.roles) return false;
  // Any verified role counts (UNT / Legacy / Industry / Other-Edu), not just the
  // single UNT-email role — Legacy members were being told they weren't verified.
  const ids =
    config.verifiedRoleIds && config.verifiedRoleIds.length
      ? config.verifiedRoleIds
      : config.verifiedRoleId
        ? [config.verifiedRoleId]
        : [];
  return ids.some((id) => member.roles.cache.has(id));
}

/** Config interest roles that actually exist in the guild (skip stale ids). */
function liveSelfRoles(member) {
  const guildRoles = member.guild && member.guild.roles && member.guild.roles.cache;
  if (!guildRoles) return config.selfRoles;
  return config.selfRoles.filter((r) => guildRoles.has(r.id));
}

/** The multi-select of interest roles, pre-checked with what the member has. */
function buildMenuRow(member) {
  const roles = liveSelfRoles(member);
  const menu = new StringSelectMenuBuilder()
    .setCustomId(SELECT_ID)
    .setPlaceholder("Pick the teams / projects you're interested in")
    .setMinValues(0)
    .setMaxValues(Math.max(1, roles.length))
    .addOptions(
      roles.map((r) => {
        const opt = { label: r.label, value: r.id, default: member.roles.cache.has(r.id) };
        if (r.emoji) opt.emoji = r.emoji;
        if (r.description) opt.description = r.description;
        return opt;
      })
    );
  return new ActionRowBuilder().addComponents(menu);
}

/** The persistent "Choose your interests" button (posted by /postrolesmenu). */
function openButtonRow() {
  return new ActionRowBuilder().addComponents(
    new ButtonBuilder()
      .setCustomId(OPEN_ID)
      .setLabel('Choose your interests')
      .setEmoji('🎛️')
      .setStyle(ButtonStyle.Primary)
  );
}

function notVerifiedReply() {
  return {
    content:
      `You need to verify first before picking interest roles — head to <#${config.verifyChannelId}> and run \`/verify\`.`,
    flags: MessageFlags.Ephemeral,
  };
}

/** Show the personalised, ephemeral picker (from /roles or the button). */
async function showMenu(interaction) {
  if (!isVerified(interaction.member)) {
    await interaction.reply(notVerifiedReply());
    return;
  }
  await interaction.reply({
    content: 'Choose the teams and projects you want to follow. Deselect to leave one.',
    components: [buildMenuRow(interaction.member)],
    flags: MessageFlags.Ephemeral,
  });
}

/** Apply a select submission: sync the member's interest roles to the choice. */
async function applySelection(interaction) {
  if (!isVerified(interaction.member)) {
    await interaction.update({ content: notVerifiedReply().content, components: [] });
    return;
  }
  const chosen = new Set(interaction.values);
  const managed = liveSelfRoles(interaction.member);
  const added = [];
  const removed = [];
  for (const r of managed) {
    const has = interaction.member.roles.cache.has(r.id);
    try {
      if (chosen.has(r.id) && !has) {
        await interaction.member.roles.add(r.id, 'self-assigned interest role');
        added.push(r.label);
      } else if (!chosen.has(r.id) && has) {
        await interaction.member.roles.remove(r.id, 'self-removed interest role');
        removed.push(r.label);
      }
    } catch (err) {
      log.error('selfroles: failed to sync role', r.label, err.message);
    }
  }
  let msg;
  if (!added.length && !removed.length) {
    msg = "No changes — you're all set. ✅";
  } else {
    const parts = [];
    if (added.length) parts.push(`**Added:** ${added.join(', ')}`);
    if (removed.length) parts.push(`**Removed:** ${removed.join(', ')}`);
    msg = `Updated your interests. ${parts.join(' · ')}`;
  }
  await interaction.update({ content: msg, components: [] });
}

module.exports = { OPEN_ID, SELECT_ID, showMenu, applySelection, openButtonRow, isVerified };
