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

$db = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_NAME);
$db->set_charset(DATABASE_CHARSET);

date_default_timezone_set(TIMEZONE);

$untrobotics = new untrobotics();

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
		
		//extras
		if ($userinfo['sandbox']) {
			$untrobotics->set_sandbox(true);
		}
	}
	
	if ($auth == true) {
		if (!is_array($auth_result)) {
			die(header("Location: /auth/login"));
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

function email($to, $subject, $message, $replyto = false, $headers = NULL, $attachments = array()) {
	global $db;
	require_once(BASE . "/api/sendgrid/sendgrid-php.php");
	if (count($attachments)) {
	}
	
	$email = new \SendGrid\Mail\Mail(); 
	$email->setFrom("no-reply@untrobotics.com", "UNT Robotics");
	$email->setSubject($subject);
	
	if ($replyto) {
		$email->setReplyTo($replyto);
	}
	
	if (is_array($to)) {
		$email->addTo($to[0], $to[1]);
	} else {
		$email->addTo($to);
	}
	
	$email->addContent("text/html", $message);
	
	foreach ($attachments as $attachment) {
        $email->addAttachment(
			$attachment['content'],
			$attachment['type'],
			$attachment['filename'],
			$attachment['disposition'],
			$attachment['content_id']
		);
	}
	
	$sendgrid = new \SendGrid(SENDGRID_API_KEY);
	
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
	
	try {
		$response = $sendgrid->send($email);
		$status_code = $response->statusCode();
		
		if ($status_code >= 200 && $status_code <= 299) {
			$insert_id = $db->insert_id;
			if (is_numeric($insert_id)) {
				$db->query('UPDATE sent_emails SET status = 1 WHERE id = "' . $db->real_escape_string($insert_id) . '"');
			}
			
			return true;
		}
	} catch (Exception $e) {
		//echo 'Caught exception: '. $e->getMessage() ."\n";
		// TODO: Alerting
	}
	
	return false;
	
	/*
	$replyto = ($replyto ? "$replyto" : EMAIL_NAME . ' <' . EMAIL_USER . '@' . EMAIL_DOMAIN . '>');
	if (!$headers || $headers == NULL) {
		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
		$headers .= 'From: ' . EMAIL_NAME . ' <no-reply@' . EMAIL_DOMAIN . '>' . "\r\n" .
		'Reply-To: ' . $replyto . "\r\n" .
		'X-Mailer: PHP/' . phpversion();
	}
	*/
	
}

function get_fingerprint() {
	return md5($_SERVER['HTTP_USER_AGENT']);
}

function auth($auth_level = 1) {
	global $db;
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
					if ($auth_level == 2) {
						if ($userinfo['is_admin'] == 1) {
							return array($userinfo, $auth_session);
						}
					} else {
						return array($userinfo, $auth_session);
					}
				}
			}
		}
	}
	return false;
}

$timezones = timezone_identifiers_list();
