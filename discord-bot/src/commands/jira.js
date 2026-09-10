'use strict';

const { SlashCommandBuilder, MessageFlags } = require('discord.js');
const config = require('../config');
const log = require('../logger');

/**
 * /jira – officers file a task onto the website backlog (Jira project URW)
 * straight from Discord. Mirrors the /addevent thin-bot pattern: the bot POSTs
 * to an internal website endpoint (api/internal/jira-create.php) that owns the
 * Jira credentials; the bot itself only holds the shared internal secret.
 */
module.exports = {
  data: new SlashCommandBuilder()
    .setName('jira')
    .setDescription('File a task on the website backlog (Officers only).')
    .addStringOption((o) =>
      o.setName('summary').setDescription('Short title of the task').setRequired(true).setMaxLength(250))
    .addStringOption((o) =>
      o.setName('description').setDescription('Details (optional)').setRequired(false).setMaxLength(1000))
    .addStringOption((o) =>
      o.setName('type').setDescription('Issue type (default Task)').setRequired(false)
        .addChoices(
          { name: 'Task', value: 'Task' },
          { name: 'Bug', value: 'Bug' },
          { name: 'Story', value: 'Story' },
        )),

  async execute(interaction) {
    await interaction.deferReply({ flags: MessageFlags.Ephemeral });

    // Officer-role gate (same as /addevent).
    const roleId = config.officerRoleId;
    const hasRole = roleId && interaction.member && interaction.member.roles
      && interaction.member.roles.cache && interaction.member.roles.cache.has(roleId);
    if (!hasRole) {
      await interaction.editReply(
        roleId ? `This command is for <@&${roleId}>s only.`
               : 'This command is restricted, but no officer role is configured. Ask an admin.'
      );
      return;
    }

    if (!config.internalEmailSecret) {
      log.error('jira: INTERNAL_EMAIL_SECRET not configured');
      await interaction.editReply('Jira isn’t configured yet (missing internal secret). Ask an admin.');
      return;
    }

    const summary = interaction.options.getString('summary').trim();
    const description = (interaction.options.getString('description') || '').trim();
    const type = interaction.options.getString('type') || 'Task';

    let res;
    try {
      res = await fetch(config.jiraEndpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Internal-Secret': config.internalEmailSecret,
        },
        body: JSON.stringify({
          summary,
          description: description || undefined,
          issuetype: type,
          requestedBy: interaction.user.tag,
        }),
      });
    } catch (err) {
      log.error('jira: endpoint unreachable', err.message);
      await interaction.editReply('Couldn’t reach the Jira service. Try again in a moment.');
      return;
    }

    let body = {};
    try {
      body = await res.json();
    } catch (_) {
      /* non-JSON response */
    }

    if (!res.ok || !body.ok) {
      log.warn('jira: endpoint error', res.status, JSON.stringify(body));
      await interaction.editReply(
        `Couldn’t create the task${body && body.error ? `: ${body.error}` : ` (HTTP ${res.status})`}.`
      );
      return;
    }

    await interaction.editReply(`Created **${body.key}** (${type}) — ${body.url}`);
  },
};
