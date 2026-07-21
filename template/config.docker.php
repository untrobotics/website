<?php
/**
 * Container / Kubernetes configuration.
 *
 * This file is copied to template/config.php inside the Docker image (the real
 * template/config.php is gitignored and never enters the image). Secrets and
 * deployment-specific values are read from the environment so the same image
 * can be driven by docker-compose locally and by Kubernetes Secrets/ConfigMaps
 * in the cluster. Non-secret public identifiers are kept as literals.
 */

require_once(__DIR__ . "/constants.php");

/** Read an environment variable, falling back to a default when unset/empty. */
function env(string $key, string $default = ''): string {
    $v = getenv($key);
    return ($v === false || $v === '') ? $default : $v;
}

define('BASE', __DIR__ . '/..'); // no trailing slash

define('ENVIRONMENT',
    env('APP_ENV', 'development') === 'production'
        ? Environment::PRODUCTION
        : Environment::DEVELOPMENT
);
define('TIMEZONE', env('TIMEZONE', 'America/Chicago'));

define('WEBSITE_NAME',   env('WEBSITE_NAME', 'UNT Robotics'));
define('WEBSITE_DOMAIN', env('WEBSITE_DOMAIN', 'untrobotics.com'));
define('WEBSITE_URL',    env('WEBSITE_URL', 'https://www.untrobotics.com')); // no trailing slash

define('HASH_SALT', env('HASH_SALT')); // keep absolutely secret

define('COOKIE_PREFIX', env('COOKIE_PREFIX', 'UNT_ROBOTICS'));

define('EMAIL_DOMAIN',  env('EMAIL_DOMAIN', 'untrobotics.com'));
define('EMAIL_USER',    env('EMAIL_USER', 'hello'));
define('EMAIL_NAME',    env('EMAIL_NAME', 'UNT Robotics'));
define('EMAIL_SUPPORT', env('EMAIL_SUPPORT', 'hello@untrobotics.com'));

// Database — host/name/user/pass come from the environment (compose service or K8s Secret).
define('DATABASE_HOST',     env('DATABASE_HOST', 'mysql'));
define('DATABASE_USER',     env('DATABASE_USER', 'untrobotics-web'));
define('DATABASE_PASSWORD', env('DATABASE_PASSWORD'));
define('DATABASE_NAME',     env('DATABASE_NAME', 'untrobotics'));
define('DATABASE_CHARSET',  env('DATABASE_CHARSET', 'utf8mb4'));

define('SESSION_TIMEOUT', (int) env('SESSION_TIMEOUT', '1440'));

define('PHONE_NUMBER',                       '9403040795');
define('PHONE_NUMBER_FORMATTED',             '(940) 304-0795');
define('PHONE_NUMBER_INTERNATIONAL_PREFIX',  '+1');

define('BOTATHON_REGISTRATION_LIMIT', (int) env('BOTATHON_REGISTRATION_LIMIT', '60'));
define('BOTATHON_ENFORCE_PROMISE',    env('BOTATHON_ENFORCE_PROMISE', 'false') === 'true');
define('BOTATHON_SEASON',             (int) env('BOTATHON_SEASON', '5'));

define('SOCIAL_MEDIA_FACEBOOK_URL',  'https://www.facebook.com/untrobotics');
define('SOCIAL_MEDIA_TWITTER_URL',   'https://twitter.com/untrobotics');
define('SOCIAL_MEDIA_INSTAGRAM_URL', 'https://www.instagram.com/untrobotics/');
define('SOCIAL_MEDIA_TWITTER_HANDLE', 'untrobotics');

define('API_SECRET', env('API_SECRET'));

define('TWILIO_ACCOUNT_SID',        env('TWILIO_ACCOUNT_SID'));
define('TWILIO_AUTH_TOKEN',         env('TWILIO_AUTH_TOKEN'));
define('TWILIO_FIND_FIRST_QUEUE',   env('TWILIO_FIND_FIRST_QUEUE', 'find-first'));
define('TWILIO_FIND_FIRST_QUEUE_SID', env('TWILIO_FIND_FIRST_QUEUE_SID'));
// A2P 10DLC: outbound SMS must be sent through the Messaging Service that
// carries the approved campaign, otherwise carriers reject with error 30034.
define('TWILIO_MESSAGING_SERVICE_SID', env('TWILIO_MESSAGING_SERVICE_SID', 'MGb6d953f5f877fa5175519f04db5aa6cb'));

define('PRINTFUL_API_KEY', env('PRINTFUL_API_KEY'));
// Shared secret for the Printful webhook (carried as ?secret=... in the webhook
// URL). Leave unset to skip verification; set it — and add ?secret=<value> to the
// webhook URL in the Printful dashboard — to require it.
define('PRINTFUL_WEBHOOK_SECRET', env('PRINTFUL_WEBHOOK_SECRET', ''));

// Brevo (Sendinblue) v3 API — used to push newsletter sign-ups into a contact
// list and to send the newsletter as a self-throttled drip. Leave the key unset
// to store sign-ups only in the newsletter_signups table.
define('BREVO_API_KEY', env('BREVO_API_KEY', ''));
define('BREVO_NEWSLETTER_LIST_ID', (int) env('BREVO_NEWSLETTER_LIST_ID', '3'));

// Daily send budget for the free Brevo plan (300/day, shared by transactional +
// marketing). The drip sender never sends past DAILY_LIMIT - TRANSACTIONAL_RESERVE,
// so transactional email always keeps that many sends of headroom on Brevo each
// day (with the Postfix relay as the hard failover beyond that).
define('BREVO_DAILY_LIMIT', (int) env('BREVO_DAILY_LIMIT', '300'));
define('BREVO_TRANSACTIONAL_RESERVE', (int) env('BREVO_TRANSACTIONAL_RESERVE', '50'));

// Non-secret GroupMe channel→bot identifiers.
define('CHANNEL_TO_BOT', array(
    '52445482' => 'e154fe069a5a0d2a1e5cdfff8a', // officers
    '52445759' => 'befee42980f20ae879bcc66f2f', // retirees
    '46388436' => '52377d285d052fbb53f048526d', // r5
    '48461315' => '7db1a8992eb89135bf712bb114', // announcements
    '48461300' => 'c4c7045f665142cc72aac214eb', // general
    '47432153' => '03b3b464207277aba8e7087a05', // test
));

define('GROUPME_ACCESS_TOKEN',       env('GROUPME_ACCESS_TOKEN'));
define('GROUPME_OFFICER_CHANNEL_ID', env('GROUPME_OFFICER_CHANNEL_ID'));
define('GROUPME_MAX_MENTIONS',       25);

define('DISCORD_CLIENT_PUBLIC_KEY',  env('DISCORD_CLIENT_PUBLIC_KEY'));
define('DISCORD_APP_API_URL',        'https://discordapp.com/api');
define('DISCORD_INVITE_URL',         env('DISCORD_INVITE_URL', 'https://discord.gg/aaaaaaa'));
define('DISCORD_APP_REDIRECT_URI',   env('DISCORD_APP_REDIRECT_URI', 'https://www.untrobotics.com/auth/discord'));
define('DISCORD_ADMIN_BOT_TOKEN',    env('DISCORD_ADMIN_BOT_TOKEN'));
define('DISCORD_APP_CLIENT_ID',      env('DISCORD_APP_CLIENT_ID'));
define('DISCORD_APP_CLIENT_SECRET',  env('DISCORD_APP_CLIENT_SECRET'));
define('DISCORD_ADMIN_CHANNEL_ID',    env('DISCORD_ADMIN_CHANNEL_ID', '674703370971250708'));
define('DISCORD_GENERAL_CHANNEL_ID',  '639209564658729012');
define('DISCORD_WEB_LOGS_CHANNEL_ID', '759511118976122940');
define('DISCORD_DEV_WEB_LOGS_CHANNEL_ID', '762363893392736257');
define('DISCORD_GUILD_ID',            '639209564188704768');
define('DISCORD_GOOD_STANDING_ROLE_ID', '639212968067989524');
define('DISCORD_GOOD_STANDING_DEPENDENT_ROLES', array(
    '755885806970863687', // Rec Bots
    '755887554057994312', // Rocketry
    '755889387354587187', // Competition
    '846168709605359626', // Cyber Security
    '755952946566660206',  // Web/Ops Team
));

define('PAYPAL_PDT_ID_TOKEN',         env('PAYPAL_PDT_ID_TOKEN'));
define('PAYPAL_SANDBOX_PDT_ID_TOKEN', env('PAYPAL_SANDBOX_PDT_ID_TOKEN'));
define('PAYPAL_BUSINESS_ID',          env('PAYPAL_BUSINESS_ID'));
define('PAYPAL_SANDBOX_BUSINESS_ID',  env('PAYPAL_SANDBOX_BUSINESS_ID'));
define('PAYPAL_IPN_URL',              WEBSITE_URL . '/paypal/ipn.php');
define('PAYPAL_DEFAULT_RETURN_URL',   WEBSITE_URL . '/paypal/complete');
define('PAYPAL_IMAGE_LOGO',           WEBSITE_URL . '/images/unt-robotics-paypal-logo.svg');
define('PAYPAL_API_ACCOUNT',          env('PAYPAL_API_ACCOUNT'));
define('PAYPAL_API_PASSWORD',         env('PAYPAL_API_PASSWORD'));
define('PAYPAL_API_SIGNATURE',        env('PAYPAL_API_SIGNATURE'));
define('PAYPAL_SANDBOX_API_ACCOUNT',  env('PAYPAL_SANDBOX_API_ACCOUNT'));
define('PAYPAL_SANDBOX_API_PASSWORD', env('PAYPAL_SANDBOX_API_PASSWORD'));
define('PAYPAL_SANDBOX_API_SIGNATURE', env('PAYPAL_SANDBOX_API_SIGNATURE'));

// PayPal REST app (Orders v2 / JS SDK Smart Buttons). Created in the PayPal
// Developer dashboard (one app for live, one for sandbox). The sandbox-vs-live
// pair (and the api-m.sandbox.paypal.com vs api-m.paypal.com endpoint) is chosen
// at request time by $untrobotics->get_sandbox(), exactly like the old buttons.
define('PAYPAL_CLIENT_ID',          env('PAYPAL_CLIENT_ID'));
define('PAYPAL_CLIENT_SECRET',      env('PAYPAL_CLIENT_SECRET'));
define('PAYPAL_SANDBOX_CLIENT_ID',  env('PAYPAL_SANDBOX_CLIENT_ID'));
define('PAYPAL_SANDBOX_CLIENT_SECRET', env('PAYPAL_SANDBOX_CLIENT_SECRET'));

// Stripe — Checkout + Apple Pay. The pk_test/sk_test vs pk_live/sk_live prefix
// already encodes test-vs-live, so no separate sandbox flag is needed.
// STRIPE_WEBHOOK_SECRET is the "whsec_..." signing secret of the webhook
// endpoint (https://<host>/api/stripe/webhook.php), created in the dashboard.
define('STRIPE_PUBLISHABLE_KEY', env('STRIPE_PUBLISHABLE_KEY'));
define('STRIPE_SECRET_KEY',      env('STRIPE_SECRET_KEY'));
define('STRIPE_WEBHOOK_SECRET',  env('STRIPE_WEBHOOK_SECRET'));

define('DYNDNS_ALLOWED_SUPERDOMAINS', array('untrobotics.com'));
define('DYNDNS_FORCE_SUBDOMAIN',      'dyndns');

define('NAMECOM_API_USERNAME', env('NAMECOM_API_USERNAME'));
define('NAMECOM_API_KEY',      env('NAMECOM_API_KEY'));

define('IP2LOCATION_EMAIL', env('IP2LOCATION_EMAIL'));
define('IP2LOCATION_PASS',  env('IP2LOCATION_PASS'));

define('TIMEZONEDB_API_KEY', env('TIMEZONEDB_API_KEY'));

// SendGrid stays INBOUND-only (parse webhook ingest); outbound no longer uses it.
define('SENDGRID_API_KEY', env('SENDGRID_API_KEY'));

// Shared secret protecting api/sendgrid-inbound/parse.php (the OrgSync/CampusLabs
// auto-welcome ingest). The self-hosted Postfix relay's pipe script
// (mail/orgsync-ingest.py) sends this in the X-Ingest-Secret header; requests
// without a matching value get 403. Set the SAME value on the mail pod
// (untrobotics-mail Secret `mail-ingest`) and in web-secrets. Empty = fail closed.
define('INGEST_SECRET', env('INGEST_SECRET'));

// Outbound email relay. email() (template/top.php) sends via plain SMTP on the
// trusted internal hop to the self-hosted Postfix relay; the relay does the real
// TLS out to the recipient's MX. No auth/STARTTLS on this internal :25 hop.
define('SMTP_HOST', env('SMTP_HOST', 'mail.untrobotics-mail.svc.cluster.local'));
define('SMTP_PORT', (int) env('SMTP_PORT', '25'));

// Primary outbound smarthost: Brevo (authenticated SMTP over STARTTLS on :587).
// When BREVO_SMTP_HOST + BREVO_SMTP_USER are set, email() sends via Brevo first
// (trusted IPs => inbox placement) and FAILS OVER to the Postfix relay above if
// Brevo is unreachable or over quota. Leave unset to use Postfix only.
define('BREVO_SMTP_HOST', env('BREVO_SMTP_HOST', 'smtp-relay.brevo.com'));
define('BREVO_SMTP_PORT', (int) env('BREVO_SMTP_PORT', '587'));
define('BREVO_SMTP_USER', env('BREVO_SMTP_USER'));
define('BREVO_SMTP_PASS', env('BREVO_SMTP_PASS'));

// Shared secret guarding the internal email endpoint (api/internal/send-email.php)
// that the Discord bot POSTs to so all mail flows through one send path. Must
// match the bot's INTERNAL_EMAIL_SECRET. Empty = the endpoint fails closed (403).
define('INTERNAL_EMAIL_SECRET', env('INTERNAL_EMAIL_SECRET'));

define('GOOGLE_CLIENT_API_KEY',          env('GOOGLE_CLIENT_API_KEY'));
define('GOOGLE_CLIENT_APP_NAME',         'UNT Robotics');
define('GOOGLE_INTEREST_SPREADSHEET_ID', env('GOOGLE_INTEREST_SPREADSHEET_ID'));
define('GOOGLE_MAJORS_SPREADSHEET_RANGE', 'Form Responses 1!A2:F');

define('GOOGLE_RECAPTCHA_KEY', env('GOOGLE_RECAPTCHA_KEY'));

define('FTP_USER_CONFIG_FILE', env('FTP_USER_CONFIG_FILE'));
define('FTP_USER_CONFIG_DIR',  env('FTP_USER_CONFIG_DIR'));
