'use strict';

const { SlashCommandBuilder } = require('discord.js');
const selfroles = require('../lib/selfroles');

module.exports = {
  data: new SlashCommandBuilder()
    .setName('roles')
    .setDescription("Pick which teams / projects you're interested in."),

  async execute(interaction) {
    await selfroles.showMenu(interaction);
  },
};
