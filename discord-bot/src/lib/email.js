'use strict';

const sgMail = require('@sendgrid/mail');
const { config } = require('../config');
const log = require('../logger');

let initialised = false;

function init() {
  if (initialised) return;
  if (!config.sendgridApiKey) {
    throw new Error('SENDGRID_API_KEY is not set — cannot send verification email');
  }
  sgMail.setApiKey(config.sendgridApiKey);
  initialised = true;
}

/**
 * Send a verification code to a UNT address via SendGrid.
 * Throws on failure so the caller can surface a "couldn't send" message and
 * (importantly) NOT persist a code the user never received.
 *
 * @param {string} to       recipient email
 * @param {string} code     the plaintext numeric code (only lives in memory)
 * @param {number} ttlMins  minutes until the code expires (for the body copy)
 */
async function sendVerificationCode(to, code, ttlMins) {
  init();
  const msg = {
    to,
    from: { email: config.emailFrom, name: config.emailFromName },
    subject: `Your UNT Robotics Discord verification code: ${code}`,
    text:
      `Welcome to the UNT Robotics Discord!\n\n` +
      `Your verification code is: ${code}\n\n` +
      `Enter it in the verify channel with:  /token ${code}\n\n` +
      `This code expires in ${ttlMins} minute(s). If you did not request this, ` +
      `you can safely ignore this email.\n\n` +
      `— UNT Robotics`,
    html:
      `<p>Welcome to the <strong>UNT Robotics</strong> Discord!</p>` +
      `<p>Your verification code is:</p>` +
      `<p style="font-size:28px;font-weight:bold;letter-spacing:4px;margin:8px 0;">${code}</p>` +
      `<p>Enter it in the verify channel with <code>/token ${code}</code>.</p>` +
      `<p>This code expires in <strong>${ttlMins} minute(s)</strong>. ` +
      `If you did not request this, you can safely ignore this email.</p>` +
      `<p>— UNT Robotics</p>`,
  };

  try {
    await sgMail.send(msg);
    log.info('email: verification code sent', `to=${to}`);
  } catch (err) {
    const detail =
      err && err.response && err.response.body
        ? JSON.stringify(err.response.body)
        : err.message;
    log.error('email: SendGrid send failed', detail);
    throw new Error('Failed to send verification email');
  }
}

module.exports = { sendVerificationCode };
