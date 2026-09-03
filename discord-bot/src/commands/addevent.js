'use strict';

const {
  SlashCommandBuilder,
  MessageFlags,
  GuildScheduledEventEntityType,
  GuildScheduledEventPrivacyLevel,
} = require('discord.js');
const { config } = require('../config');
const log = require('../logger');

/**
 * /addevent – officers add an event to the club Google Calendar (the one the
 * /events page embeds). The bot stays thin: it validates input and the Officer
 * role, then POSTs to the website's internal endpoint, which owns the Google
 * Calendar service-account credentials and makes the API call. Same pattern as
 * email/SMS (see api/internal/send-email.php).
 */

// Accept "18:00", "6:00pm", "6pm", "6:30 PM". Returns "HH:MM" (24h) or null.
function parseTime(raw) {
  if (!raw) return null;
  const m = String(raw).trim().toLowerCase().match(/^(\d{1,2})(?::(\d{2}))?\s*(am|pm)?$/);
  if (!m) return null;
  let h = parseInt(m[1], 10);
  const min = m[2] ? parseInt(m[2], 10) : 0;
  const ap = m[3];
  if (min > 59) return null;
  if (ap) {
    if (h < 1 || h > 12) return null;
    if (ap === 'pm' && h !== 12) h += 12;
    if (ap === 'am' && h === 12) h = 0;
  } else if (h > 23) {
    return null;
  }
  return `${String(h).padStart(2, '0')}:${String(min).padStart(2, '0')}`;
}

// Validate a YYYY-MM-DD string is a real calendar date.
function validDate(s) {
  if (!/^\d{4}-\d{2}-\d{2}$/.test(s)) return false;
  const [y, mo, d] = s.split('-').map(Number);
  const dt = new Date(Date.UTC(y, mo - 1, d));
  return dt.getUTCFullYear() === y && dt.getUTCMonth() === mo - 1 && dt.getUTCDate() === d;
}

// "HH:MM" -> minutes since midnight.
function toMinutes(hhmm) {
  const [h, m] = hhmm.split(':').map(Number);
  return h * 60 + m;
}

// Convert a wall-clock time ("YYYY-MM-DD" + "HH:MM") in `tz` to a UTC Date,
// correctly accounting for the zone's DST offset on that date. (Discord wants an
// absolute instant; Google Calendar took wall-clock + timeZone instead.)
function zonedToUtc(dateStr, hhmm, tz) {
  const [Y, Mo, D] = dateStr.split('-').map(Number);
  const [h, m] = hhmm.split(':').map(Number);
  const asUTC = Date.UTC(Y, Mo - 1, D, h, m, 0);
  const dtf = new Intl.DateTimeFormat('en-US', {
    timeZone: tz,
    hour12: false,
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  });
  const p = {};
  for (const part of dtf.formatToParts(new Date(asUTC))) p[part.type] = part.value;
  const tzAsUTC = Date.UTC(
    Number(p.year), Number(p.month) - 1, Number(p.day),
    Number(p.hour) % 24, Number(p.minute), Number(p.second)
  );
  return new Date(asUTC - (tzAsUTC - asUTC));
}

module.exports = {
  data: new SlashCommandBuilder()
    .setName('addevent')
    .setDescription('Add an event to the club calendar (Officers only).')
    .addStringOption((o) =>
      o.setName('title').setDescription('Event name').setRequired(true).setMaxLength(200))
    .addStringOption((o) =>
      o.setName('date').setDescription('Date, YYYY-MM-DD (e.g. 2026-09-14)').setRequired(true).setMaxLength(10))
    .addStringOption((o) =>
      o.setName('start').setDescription('Start time, e.g. 18:00 or 6pm (ignored for all-day)').setRequired(false).setMaxLength(10))
    .addStringOption((o) =>
      o.setName('end').setDescription('End time, e.g. 19:30 or 7:30pm (defaults to +1h)').setRequired(false).setMaxLength(10))
    .addStringOption((o) =>
      o.setName('location').setDescription('Where it happens (optional)').setRequired(false).setMaxLength(300))
    .addStringOption((o) =>
      o.setName('description').setDescription('Details (optional)').setRequired(false).setMaxLength(1000))
    .addBooleanOption((o) =>
      o.setName('all_day').setDescription('All-day event (no start/end time)').setRequired(false)),

  async execute(interaction) {
    await interaction.deferReply({ flags: MessageFlags.Ephemeral });

    // --- Officer-role gate ---------------------------------------------------
    const roleId = config.officerRoleId;
    const hasRole = roleId && interaction.member && interaction.member.roles
      && interaction.member.roles.cache && interaction.member.roles.cache.has(roleId);
    if (!hasRole) {
      await interaction.editReply(
        roleId
          ? `This command is for <@&${roleId}>s only.`
          : 'This command is restricted, but no officer role is configured. Ask an admin.'
      );
      return;
    }

    if (!config.internalEmailSecret) {
      log.error('addevent: INTERNAL_EMAIL_SECRET not configured');
      await interaction.editReply('Calendar isn’t configured yet (missing internal secret). Ask an admin.');
      return;
    }

    // --- Validate input ------------------------------------------------------
    const title = interaction.options.getString('title').trim();
    const date = interaction.options.getString('date').trim();
    const allDay = interaction.options.getBoolean('all_day') || false;
    const location = (interaction.options.getString('location') || '').trim();
    const description = (interaction.options.getString('description') || '').trim();

    if (!validDate(date)) {
      await interaction.editReply('That date isn’t valid. Use `YYYY-MM-DD`, e.g. `2026-09-14`.');
      return;
    }

    let startTime = null;
    let endTime = null;
    if (!allDay) {
      const rawStart = interaction.options.getString('start');
      if (!rawStart) {
        await interaction.editReply('Please give a `start` time (e.g. `18:00` or `6pm`), or set `all_day: true`.');
        return;
      }
      startTime = parseTime(rawStart);
      if (!startTime) {
        await interaction.editReply('Couldn’t read the start time “' + rawStart + '”. Try `18:00` or `6:00pm`.');
        return;
      }
      const rawEnd = interaction.options.getString('end');
      if (rawEnd) {
        endTime = parseTime(rawEnd);
        if (!endTime) {
          await interaction.editReply('Couldn’t read the end time “' + rawEnd + '”. Try `19:30` or `7:30pm`.');
          return;
        }
        if (toMinutes(endTime) <= toMinutes(startTime)) {
          await interaction.editReply('The end time must be after the start time.');
          return;
        }
      } else {
        // Default to one hour after start (same day; capped at 23:59).
        const end = Math.min(toMinutes(startTime) + 60, 23 * 60 + 59);
        endTime = `${String(Math.floor(end / 60)).padStart(2, '0')}:${String(end % 60).padStart(2, '0')}`;
      }
    }

    // --- Hand off to the website endpoint (it owns the Google credentials) ---
    const payload = {
      title,
      date,
      allDay,
      start: startTime,
      end: endTime,
      location: location || undefined,
      description: description || undefined,
      timezone: config.eventTimezone,
      requestedBy: interaction.user.tag,
    };

    let res;
    try {
      res = await fetch(config.calendarEndpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Internal-Secret': config.internalEmailSecret,
        },
        body: JSON.stringify(payload),
      });
    } catch (err) {
      log.error('addevent: endpoint unreachable', err.message);
      await interaction.editReply('Couldn’t reach the calendar service. Try again in a moment.');
      return;
    }

    let body = {};
    try {
      body = await res.json();
    } catch (_) {
      /* non-JSON response */
    }

    if (!res.ok || !body.ok) {
      log.warn('addevent: endpoint error', res.status, JSON.stringify(body));
      await interaction.editReply(
        `Couldn’t add the event${body && body.error ? `: ${body.error}` : ` (HTTP ${res.status})`}.`
      );
      return;
    }

    // --- Also create a Discord scheduled event (best-effort) -----------------
    // The calendar add already succeeded, so a failure here must not fail the
    // command. Discord shows this in the server's Events tab and – when it flips
    // to ACTIVE at start time – fires the start announcement (see
    // events/guildScheduledEventUpdate.js).
    let discordEventNote = '';
    try {
      const startAt = zonedToUtc(date, allDay ? '00:00' : startTime, config.eventTimezone);
      const endAt = zonedToUtc(date, allDay ? '23:59' : endTime, config.eventTimezone);
      if (startAt.getTime() > Date.now()) {
        await interaction.guild.scheduledEvents.create({
          name: title.slice(0, 100),
          scheduledStartTime: startAt,
          scheduledEndTime: endAt,
          privacyLevel: GuildScheduledEventPrivacyLevel.GuildOnly,
          entityType: GuildScheduledEventEntityType.External,
          entityMetadata: { location: (location || 'See the club calendar').slice(0, 100) },
          description: description ? description.slice(0, 1000) : undefined,
        });
        discordEventNote = '\n📅 Added to the server’s **Events** tab too.';
      } else {
        discordEventNote = '\n_(Skipped the Discord event – its start time is in the past.)_';
      }
    } catch (err) {
      log.warn('addevent: could not create Discord scheduled event', err.message);
      discordEventNote =
        '\n_(Calendar updated, but I couldn’t create the Discord event – check my permissions.)_';
    }

    const when = allDay
      ? `${date} (all day)`
      : `${date}, ${startTime}–${endTime} ${config.eventTimezone.split('/')[1].replace('_', ' ')}`;
    const link = body.htmlLink ? `\n${body.htmlLink}` : '';

    // Post a confirmation to the officer/admin channel – the reply above is
    // ephemeral (only the officer who ran the command sees it), so this gives the
    // team a shared record that an event was created, on the calendar + Discord.
    try {
      const adminCh = await interaction.client.channels.fetch(config.logChannelId).catch(() => null);
      if (adminCh && adminCh.isTextBased()) {
        let admin = `📅 **New event added** by <@${interaction.user.id}>\n**${title}** – ${when}`;
        if (location) admin += `\n📍 ${location}`;
        admin += discordEventNote;
        if (body.htmlLink) admin += `\n🔗 ${body.htmlLink}`;
        await adminCh.send({ content: admin, allowedMentions: { parse: [] } });
      }
    } catch (err) {
      log.warn('addevent: could not post admin confirmation', err.message);
    }

    await interaction.editReply(`✅ Added **${title}** – ${when}.${link}${discordEventNote}`);
    log.info(`addevent: "${title}" on ${date} by ${interaction.user.tag}`);
  },
};
