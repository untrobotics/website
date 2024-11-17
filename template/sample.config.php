<?php
require_once(__DIR__ . "/constants.php");

//define('BASE', 								'/var/www/untrobotics'); // also defined in .htaccess and accessible via getenv('BASE');
define('BASE',                              __DIR__ . '/..');
// ^ no trailing slash

define('ENVIRONMENT',                       Environment::DEVELOPMENT);
define('TIMEZONE', 							'America/Chicago');

define('WEBSITE_NAME', 						'UNT Robotics');
define('WEBSITE_DOMAIN', 					'untrobotics.com');
define('WEBSITE_URL',						'https://www.untrobotics.com'); // no trailing slash

define('HASH_SALT', 						''); // this ID should be kept absolutely secret ... !!

define('COOKIE_PREFIX',						'UNT_ROBOTICS');

define('EMAIL_DOMAIN', 						'untrobotics.com');
define('EMAIL_USER', 						'hello');
define('EMAIL_NAME', 						'UNT Robotics');
define('EMAIL_SUPPORT',						'hello@untrobotics.com');

define('DATABASE_HOST', 					'localhost');
define('DATABASE_USER', 					'untrobotics-web');
define('DATABASE_PASSWORD', 				'');
define('DATABASE_NAME', 					'untrobotics');
define('DATABASE_CHARSET', 					'utf8');

define('SESSION_TIMEOUT', 					1440); // 24 minutes

define('PHONE_NUMBER', 						'9403040795');
define('PHONE_NUMBER_FORMATTED', 			'(940) 304-0795');
define('PHONE_NUMBER_INTERNATIONAL_PREFIX', '+1');

define('BOTATHON_REGISTRATION_LIMIT',		60);
define('BOTATHON_ENFORCE_PROMISE',  		false);
define('BOTATHON_SEASON',  		            5);

define('SOCIAL_MEDIA_FACEBOOK_URL',			'https://www.facebook.com/untrobotics');
define('SOCIAL_MEDIA_TWITTER_URL',			'https://twitter.com/untrobotics');
define('SOCIAL_MEDIA_INSTAGRAM_URL',		'https://www.instagram.com/untrobotics/');
define('SOCIAL_MEDIA_TWITTER_HANDLE',		'untrobotics');

define('API_SECRET', 						'');

define('TWILIO_ACCOUNT_SID', 				'');
define('TWILIO_AUTH_TOKEN', 				'');
define('TWILIO_FIND_FIRST_QUEUE',			'find-first');
define('TWILIO_FIND_FIRST_QUEUE_SID',		'');

define('PRINTFUL_API_KEY',					'');

define('CHANNEL_TO_BOT', array(
	'52445482' => 'e154fe069a5a0d2a1e5cdfff8a', // officers
	'52445759' => 'befee42980f20ae879bcc66f2f', // retirees
	'46388436' => '52377d285d052fbb53f048526d', // r5
	'48461315' => '7db1a8992eb89135bf712bb114', // announcements
	'48461300' => 'c4c7045f665142cc72aac214eb', // general
	'47432153' => '03b3b464207277aba8e7087a05' // test
));

define('GROUPME_ACCESS_TOKEN', 				''); // Bot owner's access token
define('GROUPME_OFFICER_CHANNEL_ID',		'');
define('GROUPME_MAX_MENTIONS',				25);


define('DISCORD_CLIENT_PUBLIC_KEY',         '');
define('DISCORD_APP_API_URL', 				'https://discordapp.com/api');
define('DISCORD_INVITE_URL',				'https://discord.gg/aaaaaaa');
define('DISCORD_APP_REDIRECT_URI', 			'https://www.untrobotics.com/auth/discord');
define('DISCORD_ADMIN_BOT_TOKEN',			'');
define('DISCORD_APP_CLIENT_ID', 			'');
define('DISCORD_APP_CLIENT_SECRET', 		'');
define('DISCORD_ADMIN_CHANNEL_ID',			'674703370971250708');
define('DISCORD_GENERAL_CHANNEL_ID',		'639209564658729012');
define('DISCORD_WEB_LOGS_CHANNEL_ID',		'759511118976122940');
define('DISCORD_DEV_WEB_LOGS_CHANNEL_ID',	'762363893392736257');
define('DISCORD_GUILD_ID', 					'639209564188704768');
define('DISCORD_GOOD_STANDING_ROLE_ID',		'639212968067989524');
define('DISCORD_GOOD_STANDING_DEPENDENT_ROLES',	array(
        '755885806970863687', // Rec Bots
        '755887554057994312', // Rocketry
        '755889387354587187', // Competition
        '846168709605359626', // Cyber Security
        '755952946566660206' // Web/Ops Team
    )
);

define('PAYPAL_PDT_ID_TOKEN', 				'');
define('PAYPAL_SANDBOX_PDT_ID_TOKEN',		'');
define('PAYPAL_BUSINESS_ID',				'');
define('PAYPAL_SANDBOX_BUSINESS_ID',		'');
define('PAYPAL_IPN_URL',					WEBSITE_URL . '/paypal/ipn.php');
define('PAYPAL_DEFAULT_RETURN_URL',			WEBSITE_URL . '/paypal/complete');
define('PAYPAL_IMAGE_LOGO',					WEBSITE_URL . '/images/unt-robotics-paypal-logo.svg');
define('PAYPAL_API_ACCOUNT',				'');
define('PAYPAL_API_PASSWORD',				'');
define('PAYPAL_API_SIGNATURE',				'');
define('PAYPAL_SANDBOX_API_ACCOUNT',		'');
define('PAYPAL_SANDBOX_API_PASSWORD',		'');
define('PAYPAL_SANDBOX_API_SIGNATURE',		'');

define('DYNDNS_ALLOWED_SUPERDOMAINS',		array('untrobotics.com'));
define('DYNDNS_FORCE_SUBDOMAIN',			'dyndns'); // don't forget the trailing .

define('NAMECOM_API_USERNAME',				'');
define('NAMECOM_API_KEY',					'');

define('IP2LOCATION_EMAIL',					'');
define('IP2LOCATION_PASS',					'');

define('TIMEZONEDB_API_KEY',				'');

define('SENDGRID_API_KEY',					'');

define('GOOGLE_CLIENT_API_KEY',             '');
define('GOOGLE_CLIENT_APP_NAME',            'UNT Robotics');
define('GOOGLE_INTEREST_SPREADSHEET_ID',    '');
define('GOOGLE_MAJORS_SPREADSHEET_RANGE',   'Form Responses 1!A2:F');

define("GOOGLE_RECAPTCHA_KEY",              '');

define("FTP_USER_CONFIG_FILE",              '');
define("FTP_USER_CONFIG_DIR",              '');
