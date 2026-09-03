'use strict';

/**
 * Single source of truth for the messageCreate side-features.
 *
 * 1. KEYWORD_RESPONSES – substring triggers (lower-cased) → reply text. The
 *    first matching trigger wins. Keep triggers specific to avoid false hits.
 * 2. CHANNEL_REACTIONS – channelId → array of emoji to auto-react with. Empty
 *    by default; fill in with channel IDs and unicode/custom emoji as needed.
 */

// Keyword auto-responses. Only the verification-help responder remains, and the
// caller restricts it to the verification channel(s) – see messageCreate.js.
const KEYWORD_RESPONSES = [
  {
    triggers: ['how do i verify', 'how to verify', 'get verified', 'verify me', 'cant verify', "can't verify", 'not working'],
    reply:
      "To verify: type **`/verify`** here and **click the `/verify` popup** that appears " +
      "(don't just send it as a message), then put your **@unt.edu / @my.unt.edu** email " +
      "in the box – it stays private, so don't type it in chat. I'll email you a code; " +
      "enter it the same way with **`/token`**, and you'll get the **Verified UNT** role automatically.",
  },
];

// channelId -> ['👍', '🤖', ...]. Empty by default.
const CHANNEL_REACTIONS = {
  // '639209564658729012': ['👋'],
};

/**
 * Find the first keyword response whose trigger appears in `content`.
 * @returns {string|null} reply text, or null when nothing matches.
 */
function matchKeyword(content) {
  const lc = content.toLowerCase();
  for (const entry of KEYWORD_RESPONSES) {
    if (entry.triggers.some((t) => lc.includes(t))) return entry.reply;
  }
  return null;
}

/** Emoji to auto-react with for a given channel (possibly empty). */
function reactionsForChannel(channelId) {
  return CHANNEL_REACTIONS[channelId] || [];
}

module.exports = { KEYWORD_RESPONSES, CHANNEL_REACTIONS, matchKeyword, reactionsForChannel };
