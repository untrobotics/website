'use strict';

const { Events, MessageFlags } = require('discord.js');
const log = require('../logger');
const reminders = require('../lib/reminders');
const selfroles = require('../lib/selfroles');

module.exports = {
  name: Events.InteractionCreate,
  once: false,
  async execute(interaction) {
    // Button router (reminder "Mark done" buttons live outside the slash flow).
    if (interaction.isButton()) {
      if (interaction.customId.startsWith('remind_done:')) {
        try {
          await reminders.handleDoneButton(interaction);
        } catch (err) {
          log.error('interactionCreate: remind_done button failed', err.stack || err.message);
        }
      } else if (interaction.customId === selfroles.OPEN_ID) {
        try {
          await selfroles.showMenu(interaction);
        } catch (err) {
          log.error('interactionCreate: selfroles open failed', err.stack || err.message);
        }
      }
      return;
    }

    // Interest-role picker (StringSelectMenu) – sync the member's roles.
    if (interaction.isStringSelectMenu() && interaction.customId === selfroles.SELECT_ID) {
      try {
        await selfroles.applySelection(interaction);
      } catch (err) {
        log.error('interactionCreate: selfroles select failed', err.stack || err.message);
      }
      return;
    }

    // Slash-command router. `client.commands` is populated in index.js.
    if (!interaction.isChatInputCommand()) return;

    const command = interaction.client.commands.get(interaction.commandName);
    if (!command) {
      log.warn('interactionCreate: unknown command', interaction.commandName);
      return;
    }

    try {
      await command.execute(interaction);
    } catch (err) {
      log.error(`interactionCreate: /${interaction.commandName} failed`, err.stack || err.message);
      const payload = {
        content: 'Sorry, something went wrong running that command.',
        flags: MessageFlags.Ephemeral,
      };
      try {
        if (interaction.deferred || interaction.replied) {
          await interaction.editReply(payload);
        } else {
          await interaction.reply(payload);
        }
      } catch (_) {
        /* interaction already gone – nothing more to do */
      }
    }
  },
};
