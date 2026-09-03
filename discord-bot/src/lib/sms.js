'use strict';

const { config } = require('../config');
const log = require('../logger');

// When an officer REPLIES (Discord native reply) to an "Received SMS Message"
// embed the admin bot posted, text the original sender back with the reply's
// content – via the website's single internal SMS endpoint. Returns true if the
// message was handled as an SMS reply (so the caller can stop).
async function handleSmsReply(message) {
  if (!message.reference || !message.reference.messageId) return false;

  let referenced;
  try {
    referenced = await message.channel.messages.fetch(message.reference.messageId);
  } catch (_) {
    return false;
  }

  // Must be a reply to one of OUR own incoming-SMS embeds (same bot user posts
  // them via the PHP AdminBot, which shares this bot's token).
  if (!referenced || !message.client.user || referenced.author.id !== message.client.user.id) return false;
  const embed = referenced.embeds && referenced.embeds[0];
  if (!embed || embed.title !== 'Received SMS Message') return false;

  // Pull the sender's number from the embed ("**FROM:** +1...").
  const desc = embed.description || '';
  const m = desc.match(/\*\*FROM:\*\*\s*(\+?\d{10,15})/i);
  if (!m) {
    await message.react('🚫').catch(() => {});
    return true;
  }
  const to = m[1];
  const body = (message.content || '').trim();
  // Forward any image/video/audio attachments as MMS media. Discord CDN URLs are
  // public, so Twilio can fetch them directly. Twilio caps media at 10 per message.
  const media = [...message.attachments.values()]
    .filter((a) => !a.contentType || /^(image|video|audio)\//i.test(a.contentType))
    .map((a) => a.url)
    .slice(0, 10);
  if (!body && media.length === 0) {
    await message.react('🚫').catch(() => {});
    return true;
  }

  try {
    const res = await fetch(config.smsEndpoint, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Internal-Secret': config.internalEmailSecret || '',
      },
      body: JSON.stringify({ to, body, media }),
      signal: AbortSignal.timeout(20000),
    });
    const json = await res.json().catch(() => ({}));
    if (res.ok && json.status && json.status !== 'failed') {
      await message.react('📤').catch(() => {});
    } else {
      await message.react('📵').catch(() => {});
      log.error('sms reply: send failed', res.status, JSON.stringify(json));
    }
  } catch (err) {
    await message.react('📵').catch(() => {});
    log.error('sms reply: endpoint error', err.message);
  }
  return true;
}

module.exports = { handleSmsReply };
