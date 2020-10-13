How to make the endpoint work
_____________________________

+ Host both of the PHP files in this directory on your server

+ Fill out the information in the config file (the channel ID & API URL is pre-filled)
	+ Make a random, secure string to use as your secret code. This code is just to stop spam requests triggering the bot.
	+ Get your GroupMe Access Token from https://dev.groupme.com/

+ Send Seb the URL of your `groupme-endpoint.php` file (including the secret code, but NOT your access token)
+ Go to the URL https://www.untrobotics.com/api/groupme-register-access-token.php?token=YOUR_ACCESS_TOKEN
	+ There will be a number displayed in the format int(NUMBER)
	+ Send Seb that number, it is your user ID

+ Et voilà! Now you spam @everyone and the message will be from your own account.
