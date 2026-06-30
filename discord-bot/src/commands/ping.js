'use strict';

const { SlashCommandBuilder } = require('discord.js');

module.exports = {
  data: new SlashCommandBuilder()
    .setName('ping')
    .setDescription('Check that the bot is alive and see its latency.'),

  async execute(interaction) {
    const sent = await interaction.reply({
      content: 'Pinging…',
      ephemeral: true,
      fetchReply: true,
    });
    const roundtrip = sent.createdTimestamp - interaction.createdTimestamp;
    const ws = Math.round(interaction.client.ws.ping);
    await interaction.editReply(
      `Pong! Roundtrip **${roundtrip}ms**, gateway **${ws}ms**.`
    );
  },
};
