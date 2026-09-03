'use strict';

const { Events } = require('discord.js');
const { matchKeyword, reactionsForChannel } = require('../lib/autoresponses');
const { handleSmsReply } = require('../lib/sms');
const { config } = require('../config');
const verification = require('../lib/verification');
const log = require('../logger');

/** Post an audit line to the log channel (best-effort). */
async function postAudit(client, content) {
  if (!config.verifyUpdatesChannelId) return;
  try {
    const channel = await client.channels.fetch(config.verifyUpdatesChannelId);
    if (channel && channel.isTextBased()) await channel.send(content);
  } catch (err) {
    log.warn('messageCreate: could not post audit line', err.message);
  }
}

/**
 * Tell a user the outcome privately. Prefers a DM; if their DMs are closed,
 * falls back to a short-lived, self-deleting channel notice so the code and the
 * result never linger publicly.
 */
async function notifyPrivately(message, text) {
  try {
    await message.author.send(text);
    return;
  } catch (_) {
    try {
      const notice = await message.channel.send({
        content: `<@${message.author.id}> ${text}`,
        allowedMentions: { users: [message.author.id] },
      });
      setTimeout(() => notice.delete().catch(() => {}), 15000);
    } catch (_) {
      /* nothing more we can do */
    }
  }
}

/** Map a redeemToken failure to a friendly private message. */
function failureMessage(result) {
  switch (result.reason) {
    case 'no_request':
      return 'You haven’t requested a code yet. Run `/verify <your UNT email>` first (pick it from the slash-command menu).';
    case 'already_verified':
      return 'You’re already verified. 🎉';
    case 'locked':
      return 'Too many failed attempts – verification is locked for a little while. Try again later.';
    case 'attempt_cooldown':
      return 'Slow down a moment, then try again.';
    case 'expired':
    case 'invalid':
    default:
      if (result.burned) {
        return 'That code is invalid, and you’ve used all attempts for it. Run `/verify` to get a new one.';
      }
      if (typeof result.attemptsLeft === 'number') {
        return `That code is invalid or expired. (${result.attemptsLeft} attempt(s) left.) Or run \`/verify\` for a new one.`;
      }
      return 'That code is invalid or has expired. Run `/verify` to get a new one.';
  }
}

/**
 * Handle a verification code that was posted as a plain message in the verify
 * channel (the classic "typed `/token 1234` as chat instead of using the slash
 * command"). Redeems it, removes the public message, and reports privately.
 *
 * Returns true if the message was a verification attempt (handled or ignored),
 * so the caller can stop processing it.
 */
async function handleInlineToken(message) {
  if (!config.verifyChannelId || message.channelId !== config.verifyChannelId) return false;
  const content = (message.content || '').trim();

  // Explicit: "/token 1234", "token: 1234", "!token 1234".
  const prefixed = content.match(/^[!\/]?token[\s:=]+([A-Za-z0-9]{3,16})\b/i);
  // Bare: just the numeric code on its own.
  const bare = content.match(/^([0-9]{3,10})$/);
  const code = prefixed ? prefixed[1] : bare ? bare[1] : null;
  if (!code) return false;

  let result;
  try {
    result = await verification.redeemToken(message.author.id, code, message.author.tag);
  } catch (err) {
    log.error('messageCreate: inline token redeem threw', err.message);
    return true; // it looked like an attempt; don't fall through to other handlers
  }

  // A bare number from someone with no pending request is probably just chatter,
  // not a code – leave it alone. An explicit "/token ..." is always treated as an
  // attempt.
  if (!prefixed && result.reason === 'no_request') return true;

  if (!result.ok) {
    // Don't delete a failed attempt – leave the person's message in place and just
    // tell them privately what went wrong.
    await notifyPrivately(message, failureMessage(result));
    return true;
  }

  // Verified – now it's safe to remove the message (the code is used/burned).
  try {
    await message.delete();
  } catch (err) {
    log.warn('messageCreate: could not delete inline token message', err.message);
  }

  // Grant the Verified role, then confirm privately.
  try {
    const member = message.member || (await message.guild.members.fetch(message.author.id));
    await member.roles.add(config.verifiedRoleId, 'UNT email verification (code posted in channel)');
  } catch (err) {
    log.error('messageCreate: inline verify role add failed', err.message);
    await notifyPrivately(
      message,
      'Your email is verified, but I couldn’t assign the role (a permissions issue on my end). Please ping an officer.'
    );
    await postAudit(
      message.client,
      `:warning: Inline verify OK for <@${message.author.id}> (${result.email}) but **role assignment FAILED**: ${err.message}`
    );
    return true;
  }

  await notifyPrivately(
    message,
    'You’re verified! Welcome to UNT Robotics. 🤖✅\n' +
      '_Tip: next time pick the `/token` slash command from the menu so your code stays private._'
  );
  await postAudit(
    message.client,
    `:white_check_mark: Verified <@${message.author.id}> (\`${message.author.tag}\`, id ${message.author.id}) → ${result.email} ` +
      '(code posted in channel – auto-redeemed, message removed)'
  );
  return true;
}

const EMAIL_RE = /[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}/;

function isUntEmail(email) {
  const domain = (email.toLowerCase().split('@')[1] || '');
  return config.allowedEmailDomains.some((d) => domain === d || domain.endsWith('.' + d));
}

/**
 * The #1 verify-channel mistake: people paste their email straight into the
 * channel (or type "/verify" as chat) instead of using the /verify slash command
 * – which also leaves their email public. Catch it: delete the message when it
 * exposes an email, then privately walk them through the command. Returns true
 * if handled.
 */
async function handleStrayVerifyMessage(message) {
  if (!config.verifyChannelId || message.channelId !== config.verifyChannelId) return false;
  const content = (message.content || '').trim();
  if (!content) return false;

  const emailMatch = content.match(EMAIL_RE);
  const typedVerify = /^[!\/]?verify\b/i.test(content); // typed "/verify" as chat, not the command
  if (!emailMatch && !typedVerify) return false;

  // Note: we do NOT delete the person's message here – only a *successful*
  // verification removes a message. We just nudge them privately (and, if they
  // posted an email, suggest they delete it themselves to keep it private).
  const verifyCh = `<#${config.verifyChannelId}>`;
  const helpCh = config.verifyHelpChannelId ? `<#${config.verifyHelpChannelId}>` : '#verification-help';

  // Non-UNT email → point at manual verification instead.
  if (emailMatch && !isUntEmail(emailMatch[0])) {
    await notifyPrivately(
      message,
      "That's not a UNT email, so automatic verification won't work – it needs your " +
        '**@unt.edu** or **@my.unt.edu** address.\n\n' +
        "If you're from **another university, a high school, or you're an industry " +
        `mentor**, you're welcome here – just request **manual verification** in ${helpCh}.\n\n` +
        '_(Tip: you can delete your message above so your email doesn\'t stay public.)_'
    );
    return true;
  }

  // UNT email pasted in chat, or "/verify" typed as a message → show them how.
  await notifyPrivately(
    message,
    "Almost! Don't type your email in the channel – use the **`/verify`** *slash " +
      'command* so it stays private:\n\n' +
      `**1.** In ${verifyCh}, type \`/verify\` and **click the \`/verify\` popup** that appears above the message box.\n` +
      '**2.** Put your UNT email in the **email** box and press enter.\n' +
      "**3.** I'll email you a code – enter it the same way with **`/token`**.\n\n" +
      (emailMatch ? '_(Tip: you can delete your message above so your email doesn\'t stay public.)_' : '')
  );
  return true;
}

module.exports = {
  name: Events.MessageCreate,
  once: false,
  async execute(message) {
    // Ignore bots (and our own messages) to avoid loops.
    if (message.author.bot) return;
    if (message.client.user && message.author.id === message.client.user.id) return;
    // Only handle guild messages.
    if (!message.guild) return;

    // --- SMS reply: reply to an incoming-SMS embed -> text the sender back ---
    try {
      if (await handleSmsReply(message)) return;
    } catch (err) {
      log.warn('messageCreate: sms reply handler failed', err.message);
    }

    // --- Verify channel: a code / "/token" typed as a plain message ---------
    try {
      if (await handleInlineToken(message)) return;
    } catch (err) {
      log.warn('messageCreate: inline token handler failed', err.message);
    }

    // --- Verify channel: an email / "/verify" typed as a plain message ------
    try {
      if (await handleStrayVerifyMessage(message)) return;
    } catch (err) {
      log.warn('messageCreate: stray verify handler failed', err.message);
    }

    // --- Auto-reactions (configurable channel -> emoji) ---------------------
    const emojis = reactionsForChannel(message.channelId);
    for (const emoji of emojis) {
      try {
        await message.react(emoji);
      } catch (err) {
        log.warn('messageCreate: react failed', emoji, err.message);
      }
    }

    // --- Keyword auto-responses (verification help only) --------------------
    // Restricted to the verification channel(s) so the bot never butts into
    // general conversation.
    const verifyChannels = [config.verifyChannelId, config.verifyHelpChannelId].filter(Boolean);
    if (message.content && verifyChannels.includes(message.channelId)) {
      const reply = matchKeyword(message.content);
      if (reply) {
        try {
          await message.reply({ content: reply, allowedMentions: { repliedUser: false } });
        } catch (err) {
          log.warn('messageCreate: auto-reply failed', err.message);
        }
      }
    }
  },
};
