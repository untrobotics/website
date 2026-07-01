'use strict';

const nodemailer = require('nodemailer');
const { config } = require('../config');
const log = require('../logger');

// Outbound goes through the self-hosted Postfix relay (the untrobotics-mail
// service), same as the website's email(). SendGrid is inbound-ingest only, so
// we never touch it here. The internal hop is plain SMTP on :25 — the relay
// itself does the real TLS out to the recipient's MX.
let transporter = null;
function getTransport() {
  if (!transporter) {
    transporter = nodemailer.createTransport({
      host: config.smtpHost,
      port: config.smtpPort,
      secure: false,
      ignoreTLS: config.smtpPort === 25, // no STARTTLS on the trusted internal hop
      tls: { rejectUnauthorized: false },
      connectionTimeout: 15000,
      greetingTimeout: 10000,
    });
  }
  return transporter;
}

/**
 * Send a verification code via the Postfix relay. Throws on failure so the
 * caller can surface a "couldn't send" message and NOT persist a code the user
 * never received.
 *
 * @param {string} to       recipient email
 * @param {string} code     the plaintext numeric code (only lives in memory)
 * @param {number} ttlMins  minutes until the code expires (for the body copy)
 */
async function sendVerificationCode(to, code, ttlMins) {
  const msg = {
    from: `"${config.emailFromName}" <${config.emailFrom}>`,
    to,
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
    const info = await getTransport().sendMail(msg);
    log.info('email: verification code sent', `to=${to} id=${info.messageId}`);
  } catch (err) {
    log.error('email: SMTP send failed', err.message);
    throw new Error('Failed to send verification email');
  }
}

module.exports = { sendVerificationCode };
