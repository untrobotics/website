'use strict';

const path = require('path');
const nodemailer = require('nodemailer');
const { config } = require('../config');
const log = require('../logger');

// Header banner, bundled into the image at build time (see Dockerfile). Attached
// inline and referenced by CID so it renders without remote-image blocking —
// mirrors the site's email() (template/top.php), which embeds the same asset.
const BANNER_PATH = path.join(__dirname, '..', '..', 'assets', 'unt-robotics-email-header.jpg');
const BANNER_CID = 'untrobotics-email-header';

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

// Shared branded wrapper — kept byte-for-byte in sync with brand_email_html()
// in template/top.php so bot mail and site mail look identical.
function brandedHtml(inner) {
  return (
    '<div style="background:#f4f5f7;margin:0;padding:24px 12px;' +
    'font-family:Arial,Helvetica,sans-serif;color:#1a1a1a;">' +
    '<table role="presentation" align="center" width="600" cellpadding="0" cellspacing="0" border="0" ' +
    'style="width:100%;max-width:600px;margin:0 auto;background:#ffffff;' +
    'border-radius:10px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,0.08);">' +
    '<tr><td style="padding:0;line-height:0;">' +
    `<img src="cid:${BANNER_CID}" alt="UNT Robotics" width="600" ` +
    'style="display:block;width:100%;max-width:600px;height:auto;border:0;"></td></tr>' +
    '<tr><td style="padding:32px 36px;font-size:15px;line-height:1.6;color:#1a1a1a;">' +
    inner +
    '</td></tr>' +
    '<tr><td style="padding:22px 36px;background:#0b2545;text-align:center;' +
    'font-size:12px;line-height:1.6;color:#b8c4d4;">' +
    'UNT Robotics &middot; University of North Texas<br>' +
    '<a href="https://www.untrobotics.com" style="color:#7ec8e3;text-decoration:none;">untrobotics.com</a>' +
    ' &nbsp;&middot;&nbsp; ' +
    '<a href="mailto:hello@untrobotics.com" style="color:#7ec8e3;text-decoration:none;">hello@untrobotics.com</a>' +
    ' &nbsp;&middot;&nbsp; ' +
    '<a href="https://www.untrobotics.com/discord" style="color:#7ec8e3;text-decoration:none;">Discord</a>' +
    '</td></tr></table></div>'
  );
}

// Big code box — matches brand_email_code_box() in template/top.php.
function codeBox(code) {
  return (
    '<div style="text-align:center;margin:24px 0;">' +
    '<span style="display:inline-block;font-size:32px;font-weight:700;letter-spacing:6px;' +
    'background:#f0f4f8;border:1px solid #d0d7de;border-radius:8px;padding:14px 26px;' +
    `color:#0b2545;font-family:Consolas,Menlo,monospace;">${code}</span></div>`
  );
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
  const inner =
    `<p>Welcome to the <strong>UNT Robotics</strong> Discord!</p>` +
    `<p>Your verification code is:</p>` +
    codeBox(code) +
    `<p>Enter it in the verify channel with <code>/token ${code}</code>.</p>` +
    `<p>This code expires in <strong>${ttlMins} minute(s)</strong>. ` +
    `If you didn't request this, you can safely ignore this email.</p>` +
    `<p>All the best,<br><em>UNT Robotics Leadership</em></p>`;

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
    html: brandedHtml(inner),
    attachments: [
      { filename: 'unt-robotics-email-header.jpg', path: BANNER_PATH, cid: BANNER_CID },
    ],
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
