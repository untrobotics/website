<?php
// config
require_once('config.php');
require_once('classes/untrobotics.php');

use SendGrid\Mail\Attachment;

$base = BASE; // for legacy support

$currentCookieParams = session_get_cookie_params();
$rootDomain = '.' . WEBSITE_DOMAIN;
session_set_cookie_params(
    0,
    "/",
    $rootDomain,
    true,
    true
);
session_name(COOKIE_PREFIX . "_PHP_SESSION_ID");
session_start();

$userinfo = array(); // this will be populated later, we are effectively making this a global
$session = array();

// PHP 8.1+ makes mysqli throw mysqli_sql_exception on failed queries by default.
// This codebase relies on the classic "query() or die()" / "if (!$q)" idiom, so
// restore the pre-8.1 return-false behavior site-wide (else failed queries 500).
mysqli_report(MYSQLI_REPORT_OFF);

class mmysqli extends mysqli {
    public function __construct($host, $user, $pass, $db) {
        parent::__construct(); // no-arg: create unconnected, then real_connect (mysqli::init() is deprecated in PHP 8.3)

        if (!parent::real_connect($host, $user, $pass, $db, 3306, null, MYSQLI_CLIENT_FOUND_ROWS)) {
            die('Connect Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
        }
    }
}

$db = new mmysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_NAME);
$db->set_charset(DATABASE_CHARSET);

date_default_timezone_set(TIMEZONE);

$untrobotics = new untrobotics($db);

function head($title, $heading, $auth = false, $return = false) {
    global $base, $userinfo, $session, $untrobotics, $db;
    $default_values = array(
        2 => array("auth", true),
        3 => array("breadcrumbs", array("Home" => "/")),
        4 => array("return", false)
    );
    foreach (func_get_args() as $key => $val) {
        if ($val == NULL) {
            $$default_values[$key][0] = $default_values[$key][1];
        }
    }

    $auth_result = auth((int)$auth);
    if (is_array($auth_result)) {
        $userinfo = $auth_result[0];
        $session = $auth_result[1];
        date_default_timezone_set($userinfo['timezone']);
    }

    if ($auth == true) {
        if (!is_array($auth_result)) {
            die(header("Location: /auth/login?returnto=" . $_SERVER['REQUEST_URI']));
        }
    }

    $title = htmlspecialchars($title ? $title . " | " . WEBSITE_NAME : WEBSITE_NAME);
    $heading = htmlspecialchars($heading);

    if ($heading == true && gettype($heading) == "boolean") {
        $heading = $title;
    }

    if ($return == true) {
        ob_start();
        require("$base/template/header.php");
        $return = ob_get_clean();
        return $return;
    } else {
        require("$base/template/header.php");
        return;
    }
}

function footer($die = true) {
    global $base;
    require("$base/template/footer.php");
    if ($die == true) {
        die();
    }
}

/**
 * Wrap an email body fragment in the shared UNT Robotics branded template:
 * a responsive, centered white card with the header banner up top (inline
 * cid image) and a footer. Callers pass ONLY their inner content (paragraphs,
 * a code box, etc.); email() applies this automatically unless $branded=false.
 *
 * Improvements over the old hand-rolled version: max-width instead of a fixed
 * 500px (renders on mobile), real spacing instead of empty <div> spacers, and
 * an <img> with alt + width so it degrades cleanly when images are blocked.
 */
function brand_email_html($inner) {
    return
        '<div style="background:#f4f5f7;margin:0;padding:24px 12px;'
        . 'font-family:Arial,Helvetica,sans-serif;color:#1a1a1a;">'
        . '<table role="presentation" align="center" width="600" cellpadding="0" cellspacing="0" border="0" '
        . 'style="width:100%;max-width:600px;margin:0 auto;background:#ffffff;'
        . 'border-radius:10px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,0.08);">'
        . '<tr><td style="padding:0;line-height:0;background:#00853e;">'
        . '<img src="https://www.untrobotics.com/images/unt-robotics-email-header.jpg" alt="UNT Robotics" width="600" '
        . 'style="display:block;width:100%;max-width:600px;height:auto;border:0;"></td></tr>'
        . '<tr><td style="padding:32px 36px;font-size:15px;line-height:1.6;color:#1a1a1a;">'
        . $inner
        . '</td></tr>'
        . '<tr><td style="padding:22px 36px;background:#00853e;text-align:center;'
        . 'font-size:12px;line-height:1.6;color:#eafaef;">'
        . 'UNT Robotics &middot; University of North Texas<br>'
        . '<a href="https://www.untrobotics.com" style="color:#ffffff;text-decoration:underline;">untrobotics.com</a>'
        . ' &nbsp;&middot;&nbsp; '
        . '<a href="mailto:hello@untrobotics.com" style="color:#ffffff;text-decoration:underline;">hello@untrobotics.com</a>'
        . ' &nbsp;&middot;&nbsp; '
        . '<a href="https://www.untrobotics.com/discord" style="color:#ffffff;text-decoration:underline;">Discord</a>'
        . '</td></tr></table></div>';
}

/**
 * Reusable "big code/token" box for verification & welcome emails. Keeps the
 * look identical across the site and the Discord bot.
 */
function brand_email_code_box($code) {
    return '<div style="text-align:center;margin:24px 0;">'
        . '<span style="display:inline-block;font-size:28px;font-weight:700;letter-spacing:4px;'
        . 'background:#f0f4f8;border:1px solid #d0d7de;border-radius:8px;padding:14px 24px;'
        . 'color:#0b2545;font-family:Consolas,Menlo,monospace;">' . htmlspecialchars($code) . '</span></div>';
}

/**
 * Rate-limited admin-channel alert when the Brevo (primary) email transport
 * fails, so an expired/invalid Brevo SMTP key (it lapses after 90 days of
 * inactivity) gets noticed. Mail still goes out via the Postfix fallback, but to
 * spam — so we want a human to regenerate the key. Best effort: never throws,
 * at most one alert per 6 hours (temp-file marker).
 */
function notify_brevo_failure($detail) {
    $marker = sys_get_temp_dir() . '/untr_brevo_alert';
    if (is_readable($marker) && (time() - filemtime($marker) < 21600)) {
        return; // already alerted within the last 6h
    }
    @touch($marker);
    if (!class_exists('AdminBot')) {
        @require_once(BASE . '/api/discord/bots/admin.php');
    }
    if (class_exists('AdminBot')) {
        try {
            AdminBot::send_message(
                "\u{26A0}\u{FE0F} Brevo email send failed \u{2014} falling back to Postfix (mail delivered "
                . "but likely to spam). Regenerate the Brevo SMTP key (it expires after 90 days of "
                . "inactivity) and update BREVO_SMTP_PASS. Detail: ",
                substr((string) $detail, 0, 300)
            );
        } catch (\Throwable $e) {
            /* alerting is best-effort */
        }
    }
}

function email($to, $subject, $message, $replyto = false, $headers = NULL, $attachments = array(), $branded = true) {
    global $db;
    // Outbound now goes through the self-hosted Postfix relay via PHPMailer/SMTP.
    // SendGrid is INBOUND-ingest only (api/sendgrid-inbound/*) and no longer used
    // here. The internal hop is plain SMTP on :25 to a trusted relay (no auth/TLS);
    // the relay itself does the real TLS out to the recipient's MX.
    require_once(BASE . "/api/mailer/vendor/autoload.php");

    // Record the attempt first (status 0), then flip to 1 on a successful send —
    // preserves the original sent_emails bookkeeping and return semantics.
    $db->query("
				INSERT INTO sent_emails (
					`to`,
					`subject`,
					`message`,
					`headers`,
					`replyto`,
					`attachments`,
					`status`
				)
				VALUES (
					" . $db->real_escape_string(json_encode($to)) . ",
					" . $db->real_escape_string(json_encode($subject)) . ",
					" . $db->real_escape_string(json_encode($message)) . ",
					" . $db->real_escape_string(json_encode($headers)) . ",
					" . $db->real_escape_string($replyto) . ",
					" . $db->real_escape_string(json_encode($attachments)) . ",
					" . $db->real_escape_string(0) . "
				)"
    );
    $insert_id = $db->insert_id;

    // Apply the shared branded template (header banner + footer). The banner is
    // referenced as a HOSTED image (untrobotics.com/images/...) rather than an
    // inline attachment, so clients render it without attachment clutter. Done
    // AFTER the sent_emails insert so the DB keeps the lean original body.
    if ($branded) {
        $message = brand_email_html($message);
    }

    // Delivery transports in priority order. Brevo smarthost FIRST (trusted IPs
    // => reliable inbox placement at Gmail/Microsoft), then the self-hosted
    // Postfix relay as an automatic FAILOVER when Brevo is unreachable or over
    // its daily quota. Each attempt uses a fresh PHPMailer; a thrown send()
    // simply moves on to the next transport.
    $transports = array();
    if (defined('BREVO_SMTP_HOST') && BREVO_SMTP_HOST && defined('BREVO_SMTP_USER') && BREVO_SMTP_USER) {
        $transports[] = array(
            'label' => 'brevo',
            'host'  => BREVO_SMTP_HOST,
            'port'  => (defined('BREVO_SMTP_PORT') && BREVO_SMTP_PORT) ? BREVO_SMTP_PORT : 587,
            'auth'  => true,
            'user'  => BREVO_SMTP_USER,
            'pass'  => defined('BREVO_SMTP_PASS') ? BREVO_SMTP_PASS : '',
        );
    }
    $transports[] = array('label' => 'postfix', 'host' => SMTP_HOST, 'port' => SMTP_PORT, 'auth' => false);

    $sent = false;
    foreach ($transports as $t) {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true); // true => throw exceptions
        try {
            $mail->isSMTP();
            $mail->Host    = $t['host'];
            $mail->Port    = $t['port'];
            $mail->Timeout = 15;             // keep request-path sends snappy
            $mail->CharSet = \PHPMailer\PHPMailer\PHPMailer::CHARSET_UTF8;
            if (!empty($t['auth'])) {
                // Brevo: authenticated submission over STARTTLS (:587). Brevo signs
                // with our domain-authenticated DKIM, so alignment still holds.
                $mail->SMTPAuth    = true;
                $mail->Username    = $t['user'];
                $mail->Password    = $t['pass'];
                $mail->SMTPSecure  = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->SMTPAutoTLS = true;
            } else {
                // Postfix relay: trusted internal hop, no auth/STARTTLS on :25.
                // OpenDKIM on the relay signs the fallback path.
                $mail->SMTPAuth    = false;
                $mail->SMTPAutoTLS = false;
            }

            // Keep a filable copy of every outbound email (plus-addressed so it can
            // be filtered/foldered in Gmail).
            $mail->setFrom("no-reply@untrobotics.com", "UNT Robotics");
            $mail->addBCC("untrobotics+sebastian.thomas.king@gmail.com");

            if (is_array($to)) {
                $mail->addAddress($to[0], isset($to[1]) ? $to[1] : '');
            } else {
                $mail->addAddress($to);
            }
            if ($replyto) {
                $mail->addReplyTo($replyto);
            }
            if (is_array($headers)) {
                foreach ($headers as $name => $value) {
                    $mail->addCustomHeader($name, $value);
                }
            }

            $mail->Subject = $subject;
            $mail->isHTML(true);
            $mail->Body    = $message;
            $mail->AltBody = trim(html_entity_decode(strip_tags($message))); // plaintext fallback

            // Attachments keep the SendGrid-era shape: raw bytes, decode base64.
            foreach ($attachments as $attachment) {
                $raw         = base64_decode($attachment['content']);
                $filename    = isset($attachment['filename']) ? $attachment['filename'] : '';
                $type        = isset($attachment['type']) ? $attachment['type'] : '';
                $disposition = isset($attachment['disposition']) ? $attachment['disposition'] : 'attachment';
                $cid         = isset($attachment['content_id']) ? $attachment['content_id'] : '';
                if ($disposition === 'inline' && $cid !== '') {
                    $mail->addStringEmbeddedImage($raw, $cid, $filename, \PHPMailer\PHPMailer\PHPMailer::ENCODING_BASE64, $type);
                } else {
                    $mail->addStringAttachment($raw, $filename, \PHPMailer\PHPMailer\PHPMailer::ENCODING_BASE64, $type, $disposition);
                }
            }

            $mail->send();
            $sent = true;
            error_log("email(): delivered via {$t['label']}");
            break; // first transport that succeeds wins
        } catch (\Throwable $e) {
            // Brevo over quota / unreachable / expired key => fall through to Postfix.
            error_log("email(): transport '{$t['label']}' failed: " . $mail->ErrorInfo . ' (' . $e->getMessage() . ')');
            if ($t['label'] === 'brevo') {
                notify_brevo_failure($mail->ErrorInfo ?: $e->getMessage());
            }
        }
    }

    if ($sent && is_numeric($insert_id)) {
        $db->query('UPDATE sent_emails SET status = 1 WHERE id = "' . $db->real_escape_string($insert_id) . '"');
    }

    return $sent;
}

function get_fingerprint() {
    return md5($_SERVER['HTTP_USER_AGENT']);
}

function auth($auth_level = 1) {
    global $untrobotics, $db;
    if (isset($_COOKIE[COOKIE_PREFIX . '_SESSION_ID']) && isset($_COOKIE[COOKIE_PREFIX . '_SESSION_NAME'])) {
        $q = $db->query("SELECT * FROM auth_sessions WHERE session_id = '".$db->real_escape_string($_COOKIE[COOKIE_PREFIX . '_SESSION_ID'])."' AND session_name = '".$db->real_escape_string($_COOKIE[COOKIE_PREFIX . '_SESSION_NAME'])."' AND (expires > ".time()." OR expires = 0) LIMIT 1") or die($db->error); //or die($db->error); // this is potentially a security risk if a user sees one of these errors
        if ($q->num_rows > 0) {
            $auth_session = $q->fetch_array(MYSQLI_ASSOC);
            if (get_fingerprint() == $auth_session['fingerprint']) {
                $q = $db->query("SELECT * FROM users WHERE id = '".$db->real_escape_string($auth_session['uid'])."' LIMIT 1") or die($db->error);
                if ($q->num_rows > 0) {
                    $userinfo = $q->fetch_array(MYSQLI_ASSOC);
                    if ($auth_session['session'] == 1) {
                        $db->query("UPDATE auth_sessions SET expires = '".$db->real_escape_string(time() + SESSION_TIMEOUT)."' WHERE id = '".$auth_session['id']."' LIMIT 1");
                    }

                    //extras
                    if ($userinfo['sandbox']) {
                        $untrobotics->set_sandbox(true);
                    }

                    if ($auth_level == 2) {
                        if ($userinfo['is_admin'] == 1) {
                            return array($userinfo, $auth_session);
                        }
                        return false;
                    } else {
                        return array($userinfo, $auth_session);
                    }
                }
            }
        }
    }
    return false;
}

function is_current_user_authenticated() {
    global $userinfo, $session;
    return !empty($userinfo) && !empty($session);
}

$timezones = timezone_identifiers_list();