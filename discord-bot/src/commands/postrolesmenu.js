'use strict';

const { SlashCommandBuilder, MessageFlags } = require('discord.js');
const { config } = require('../config');
const selfroles = require('../lib/selfroles');

module.exports = {
  data: new SlashCommandBuilder()
    .setName('postrolesmenu')
    .setDescription('(Officer) Post the interest-roles picker in this channel.'),

  async execute(interaction) {
    if (!interaction.member || !interaction.member.roles.cache.has(config.officerRoleId)) {
      await interaction.reply({ content: 'Officers only.', flags: MessageFlags.Ephemeral });
      return;
    }
    await interaction.channel.send({
      content:
        '## 🎛️ Pick your interests\n' +
        'Choose the teams and projects you want to follow to unlock their channels. ' +
        "You can change these any time. You'll need to be **verified** first.",
      components: [selfroles.openButtonRow()],
    });
    await interaction.reply({ content: 'Posted the interest-roles picker here. ✅', flags: MessageFlags.Ephemeral });
  },
};
