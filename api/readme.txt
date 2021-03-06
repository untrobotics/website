Commands
	Twilio SMS
	__________
	# SMS messaging is only available in the officers channel.
	
	- SMS#<Phone Number with international prefix> <message>
		e.g.: SMS#+18176584570 Hi Seb!
		
		+ The function sends an SMS message from our official phone number to the specified recipient. Replies will be received via the main GroupMe.
		+ Multimedia messages are supported, allowing images and a handful of other files to be sent simply by attaching them to your message to GroupMe.

	- SMS#<Message SID> <message>
		e.g.: SMS#SM4e7689cdfffff6fd0dfaa8847ce0c980 Hello Seb!
	
		+ Use the incoming message SID to quickly send a response, the formatting and functionality is similar to the above method.

	Twilio Voice
	____________
	# Voice messaging is only available in the officers channel.
	
	- Dialing our phone number and pressing "9" results in the prompt to leave a voicemail.
		+ If a voicemail is left, a message appears in the GroupMe with the phone number, duration and a link to play the voicemail.

	- Pressing 1-8 forwards the call to the specified person.
	
	- Pressing 0 to get put through to the first available person is NOT YET IMPLEMENTED.

	GroupMe Specific
	________________
	- @everyone <message>
		e.g.: @everyone It's a Monday morning!
		
		+ Send a message that mentions everyone in the channel, but less obtrustively than usual.
		+ @everyone is restricted to be only accessible by officers.

	- @everyone <message> (posted from your own account, not the bot)
		
		+ Configure an endpoint for the message requests to go to that contained your GroupMe Developer Access Token.
			+ Copy `groupme-endpoint-config-example.php` & `groupme-endpoint-example.php` to your own server.
			+ Fill out the information in the config file.
			+ Register your endpoint URL (send the URL & secret code to Seb)

	- @officers <message>
		
		+ Sends a message that mentions all of the officers present in the channel.
