'use strict';

const { config } = require('../config');
const log = require('../logger');

// The bot does NOT send email itself. It POSTs the message to the website's
// single internal email path (api/internal/send-email.php), which owns the
// branded template, the Brevo -> Postfix failover, and the admin-channel alert
// on Brevo failure. Keeping one send path means one place handles Brevo
// responses, quota, and expired keys — not two.

// Big code box — matches brand_email_code_box() in template/top.php. This is the
// only presentational bit left here; the outer branded wrapper is applied by
// email() on the website side.
function codeBox(code) {
  return (
    '<div style="text-align:center;margin:24px 0;">' +
    '<span style="display:inline-block;font-size:32px;font-weight:700;letter-spacing:6px;' +
    'background:#f0f4f8;border:1px solid #d0d7de;border-radius:8px;padding:14px 26px;' +
    `color:#0b2545;font-family:Consolas,Menlo,monospace;">${code}</span></div>`
  );
}

/**
 * Send a verification code by handing it to the website's internal email
 * endpoint. Throws on failure so the caller can surface a "couldn't send"
 * message and NOT persist a code the user never received.
 *
 * @param {string} to       recipient email
 * @param {string} code     the plaintext numeric code (only lives in memory)
 * @param {number} ttlMins  minutes until the code expires (for the body copy)
 */
async function sendVerificationCode(to, code, ttlMins) {
  const body =
    `<p>Welcome to the <strong>UNT Robotics</strong> Discord!</p>` +
    `<p>Your verification code is:</p>` +
    codeBox(code) +
    `<p>Enter it in the verify channel with <code>/token ${code}</code>.</p>` +
    `<p>This code expires in <strong>${ttlMins} minute(s)</strong>. ` +
    `If you didn't request this, you can safely ignore this email.</p>` +
    `<p>All the best,<br><em>UNT Robotics Leadership</em></p>`;

  let res;
  try {
    res = await fetch(config.emailEndpoint, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Internal-Secret': config.internalEmailSecret || '',
      },
      body: JSON.stringify({
        to,
        subject: `Your UNT Robotics Discord verification code: ${code}`,
        body,
      }),
      signal: AbortSignal.timeout(20000),
    });
  } catch (err) {
    log.error('email: endpoint request failed', err.message);
    throw new Error('Failed to send verification email (endpoint unreachable)');
  }

  if (!res.ok) {
    log.error('email: endpoint returned non-OK', res.status);
    throw new Error(`Failed to send verification email (endpoint ${res.status})`);
  }

  const json = await res.json().catch(() => ({}));
  if (!json.sent) {
    throw new Error('Failed to send verification email (endpoint reported not sent)');
  }
  log.info('email: verification code sent', `to=${to}`);
}

module.exports = { sendVerificationCode };
