'use strict';

const { SlashCommandBuilder } = require('discord.js');

module.exports = {
  data: new SlashCommandBuilder()
    .setName('members')
    .setDescription('Show the server member count and how many are online.'),

  async execute(interaction) {
    await interaction.deferReply({ ephemeral: true });

    const guild = interaction.guild;
    // approximatePresenceCount/MemberCount come from a counted fetch and work
    // without the (privileged) presence intent.
    const fetched = await guild.fetch();
    const total = fetched.approximateMemberCount ?? guild.memberCount;
    const online = fetched.approximatePresenceCount;

    const lines = [`**${guild.name}** has **${total}** members.`];
    if (typeof online === 'number') lines.push(`**${online}** are online right now.`);

    await interaction.editReply(lines.join('\n'));
  },
};
