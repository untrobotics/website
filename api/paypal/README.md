# About
PHP classes to interact with PayPal's REST API

# Files
* `paypal.php` contains the class that interacts with the API, `PayPalCustomApi`
* `api-json-objects.php` contains some classes to create the objects needed in PayPal API calls. You don't need to import this to use the API (since it's already imported in `paypal.php`)
* `webhook.php` contains the class to interact with PayPal's webhook events. *This is not for interacting with the webhook API endpoints*; it reacts to webhook events sent to the webhook endpoint

# Notes
* PayPal's documentation sucks. Some fields may be required if certain fields have certain values, and this isn't specified anywhere
* The tax field is not required for PayPalItems