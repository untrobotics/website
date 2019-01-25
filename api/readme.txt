Commands
	Twilio SMS
	__________
	- SMS#<Phone Number with international prefix> <message>
		e.g.: SMS#+18176584570 Hi Seb!
		
		+ The function sends an SMS message from our official phone number to the specified recipient. Replies will be received via the main GroupMe.
		+ Multimedia messages are supported, allowing images and a handful of other files to be sent simply by attaching them to your message to GroupMe.

	- SMS#<Message SID> <message>
		e.g.: SMS#SM4e7689cdfffff6fd0dfaa8847ce0c980 Hello Seb!
	
		+ Use the incoming message SID to quickly send a response, the formatting and functionality is similar to the above method.

	Twilio Voice
	____________
	- Dialing our phone number and pressing "9" results in the prompt to leave a voicemail.
		+ If a voicemail is left, a message appears in the GroupMe with the phone number, duration and a link to play the voicemail.

	- Pressing 1-8 forwards the call to the specified person.
	
	- Pressing 0 to get put through to the first available person is NOT YET IMPLEMENTED.

	GroupMe Specific
	________________
	- @everyone <message>
		e.g.: @everyone It's a Monday morning!
		
		+ Send a message that mentions everyone in the channel, but less obtrustively than usual.
