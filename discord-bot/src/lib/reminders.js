'use strict';

/**
 * Reminders backing the /remind command (URW-83).
 *
 * A reminder is a row in the `reminders` table with a `next_fire` timestamp.
 * tick() (called on an interval from index.js) fires every due reminder by
 * posting to its channel with a "Mark done" button. If the reminder has a
 * `repeat_seconds`, it re-arms itself for that interval and keeps nagging until
 * someone clicks the button (handleDoneButton) — that's the "keep reminding
 * until it's checked off as done" behaviour.
 *
 * All times are stored/compared in UTC via MySQL's UTC_TIMESTAMP() so the bot's
 * process timezone is irrelevant.
 */

const { ActionRowBuilder, ButtonBuilder, ButtonStyle, MessageFlags } = require('discord.js');
const db = require('../db');
const log = require('../logger');
const { config } = require('../config');

// Give up on a reminder that has fired absurdly many times (safety valve).
const MAX_FIRES = 1000;

// Parse a human duration ("30m", "2h", "1d", "1h30m", "90s", "1 week") into
// seconds. Unit is disambiguated by first letter: w/d/h/m/s. Returns null if
// nothing parseable was found.
function parseDuration(str) {
  if (!str) return null;
  const re = /(\d+)\s*([a-zA-Z]+)/g;
  const perSecond = { w: 604800, d: 86400, h: 3600, m: 60, s: 1 };
  let m;
  let total = 0;
  let found = false;
  while ((m = re.exec(str)) !== null) {
    const n = parseInt(m[1], 10);
    const unit = m[2][0].toLowerCase();
    if (Object.prototype.hasOwnProperty.call(perSecond, unit) && Number.isFinite(n)) {
      total += n * perSecond[unit];
      found = true;
    }
  }
  return found ? total : null;
}

// Seconds -> friendly string, at most two units ("1 day 3 hours", "45 minutes").
function humanDuration(seconds) {
  let s = Math.round(seconds);
  const d = Math.floor(s / 86400); s -= d * 86400;
  const h = Math.floor(s / 3600); s -= h * 3600;
  const mn = Math.floor(s / 60); const sec = s - mn * 60;
  const parts = [];
  const push = (n, unit) => { if (n) parts.push(n + ' ' + unit + (n > 1 ? 's' : '')); };
  push(d, 'day');
  push(h, 'hour');
  push(mn, 'minute');
  if (!parts.length) push(sec || 0, 'second');
  return parts.slice(0, 2).join(' ');
}

async function createReminder(r) {
  const [res] = await db.pool.query(
    `INSERT INTO reminders
       (guild_id, channel_id, creator_id, target_type, target_id, body, next_fire, repeat_seconds)
     VALUES (?, ?, ?, ?, ?, ?, UTC_TIMESTAMP() + INTERVAL ? SECOND, ?)`,
    [r.guildId, r.channelId, r.creatorId, r.targetType, r.targetId, r.body, r.inSeconds, r.repeatSeconds]
  );
  return res.insertId;
}

function doneButtonRow(id) {
  return new ActionRowBuilder().addComponents(
    new ButtonBuilder()
      .setCustomId('remind_done:' + id)
      .setLabel('Mark done')
      .setEmoji('✅')
      .setStyle(ButtonStyle.Success)
  );
}

// Fire every reminder whose next_fire is in the past. Re-arms repeaters, retires
// one-shots. Called on an interval from index.js.
async function tick(client) {
  let rows;
  try {
    [rows] = await db.pool.query(
      `SELECT * FROM reminders
        WHERE done = 0 AND fire_count < ? AND next_fire <= UTC_TIMESTAMP()
        ORDER BY next_fire
        LIMIT 25`,
      [MAX_FIRES]
    );
  } catch (err) {
    log.error('reminders: due-query failed', err.message);
    return;
  }

  for (const r of rows) {
    try {
      const channel = await client.channels.fetch(r.channel_id).catch(() => null);
      if (!channel || !channel.isTextBased()) {
        await db.pool.query('UPDATE reminders SET done = 1 WHERE id = ?', [r.id]);
        log.warn(`reminders: channel ${r.channel_id} for #${r.id} is gone; retiring reminder`);
        continue;
      }

      const mention = r.target_type === 'role' ? `<@&${r.target_id}>` : `<@${r.target_id}>`;
      const repeatNote = r.repeat_seconds
        ? `\n_I’ll keep reminding every ${humanDuration(r.repeat_seconds)} until someone hits **Mark done**._`
        : '';

      await channel.send({
        content: `⏰ **Reminder** for ${mention}:\n${r.body}${repeatNote}`,
        components: [doneButtonRow(r.id)],
        allowedMentions: {
          users: r.target_type === 'user' ? [String(r.target_id)] : [],
          roles: r.target_type === 'role' ? [String(r.target_id)] : [],
        },
      });

      if (r.repeat_seconds) {
        await db.pool.query(
          'UPDATE reminders SET next_fire = UTC_TIMESTAMP() + INTERVAL ? SECOND, fire_count = fire_count + 1 WHERE id = ?',
          [r.repeat_seconds, r.id]
        );
      } else {
        await db.pool.query('UPDATE reminders SET done = 1, fire_count = fire_count + 1 WHERE id = ?', [r.id]);
      }
    } catch (err) {
      log.error(`reminders: failed firing #${r.id}`, err.message);
      // Back off 5 minutes so a persistent failure doesn't hot-loop.
      try {
        await db.pool.query(
          'UPDATE reminders SET next_fire = UTC_TIMESTAMP() + INTERVAL 300 SECOND, fire_count = fire_count + 1 WHERE id = ?',
          [r.id]
        );
      } catch (_) { /* best effort */ }
    }
  }
}

// Button handler for "Mark done". Only the creator, the target user, a member
// with the target role, or an officer may dismiss it.
async function handleDoneButton(interaction) {
  const id = parseInt(interaction.customId.split(':')[1], 10);
  if (!Number.isFinite(id)) {
    return;
  }

  const [rows] = await db.pool.query('SELECT * FROM reminders WHERE id = ? LIMIT 1', [id]);
  if (!rows.length) {
    await interaction.reply({ content: 'That reminder is no longer active.', flags: MessageFlags.Ephemeral });
    return;
  }
  const r = rows[0];

  const member = interaction.member;
  const hasRole = (rid) => !!(rid && member && member.roles && member.roles.cache && member.roles.cache.has(rid));
  const allowed =
    interaction.user.id === r.creator_id ||
    (r.target_type === 'user' && interaction.user.id === r.target_id) ||
    (r.target_type === 'role' && hasRole(r.target_id)) ||
    hasRole(config.officerRoleId);

  if (!allowed) {
    await interaction.reply({
      content: 'Only the person who set this reminder, the person it’s for, or an officer can mark it done.',
      flags: MessageFlags.Ephemeral,
    });
    return;
  }

  if (!r.done) {
    await db.pool.query('UPDATE reminders SET done = 1 WHERE id = ?', [id]);
    log.info(`reminders: #${id} marked done by ${interaction.user.tag}`);
  }

  const base = interaction.message && interaction.message.content ? interaction.message.content : '';
  await interaction.update({
    content: `${base}\n\n✅ **Done** — marked by <@${interaction.user.id}>.`,
    components: [],
    allowedMentions: { parse: [] },
  });
}

module.exports = { parseDuration, humanDuration, createReminder, tick, handleDoneButton };
