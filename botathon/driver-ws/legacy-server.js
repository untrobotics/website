#!/usr/bin/env node

var WebSocketServer = require('websocket').server;
var http = require('http');
//var https = require('https');
//var fs = require('fs');

var server = http.createServer(
//{
//      key: fs.readFileSync( '/etc/letsencrypt/live/www.untrobotics.com/privkey.pem' ),
//      cert: fs.readFileSync( '/etc/letsencrypt/live/www.untrobotics.com/cert.pem' )
//},
function(request, response) {
    console.log((new Date()) + ' Received request for ' + request.url);
    response.writeHead(404);
    response.end();
});

server.listen(81, function() {
    console.log((new Date()) + ' Server is listening on port 81');
});

wsServer = new WebSocketServer({
    httpServer: server,
    // You should not use autoAcceptConnections for production
    // applications, as it defeats all standard cross-origin protection
    // facilities built into the protocol and the browser.  You should
    // *always* verify the connection's origin and decide whether or not
    // to accept it.
    autoAcceptConnections: false
});

function isNumeric(str) {
  if (typeof str != "string") return false // we only process strings!
  return !isNaN(str) && // use type coercion to parse the _entirety_ of the string (`parseFloat` alone does not do this)...
         !isNaN(parseFloat(str)) // ...and ensure strings of whitespace fail
}

function originIsAllowed(origin) {
  // put logic here to detect whether the specified origin is allowed.
  return true;
}

function IsJsonString(str) {
	try {
		if (!isNaN(str)) {
			return false;
		}
		JSON. parse(str);
	} catch (e) {
		return false;
	}
	return true;
}

const connections_esp32 = {};
const ack_intervals = {};

wsServer.on('request', function(request) {
    if (!originIsAllowed(request.origin)) {
      // Make sure we only accept requests from an allowed origin
      request.reject();
      console.log((new Date()) + ' Connection from origin ' + request.origin + ' rejected.');
      return;
    }

    var connection;

    try {
        connection = request.accept('team');
    } catch (error) {
        console.debug("Caught error when handling request: ", error);
        return;
    }

    console.log((new Date()) + ' Connection accepted.');

    connection.on('message', function(message) {
        if (message.type === 'utf8') {
            //console.log('Received Message: ', message.utf8Data);

			try {

				if (message.utf8Data.startsWith("ESP32_TEAM_")) {
					console.log("Validate ESP32:", message.utf8Data);
					const teamNumber = message.utf8Data.replace("ESP32_TEAM_", "");
					console.log("TEAM ESP32", teamNumber);

					connections_esp32["TEAM" + teamNumber] = connection;
					connection.team_number = teamNumber;
				} else if (IsJsonString(message.utf8Data)) {
					const messageParsed = JSON.parse(message.utf8Data);

					if (!connection.team_number) {
						// validate string
						const teamNumber = messageParsed.teamNumber;
						console.log("New team connected:", teamNumber);

						if (isNaN(teamNumber)) {
							console.warn("Not a team, rejecting");
							request.reject();
						} else {
							connection.team_number = teamNumber;
						}
					}

					// command
					//console.log("Received command for team:", messageParsed);
					if (connections_esp32["TEAM"+connection.team_number]) {
						//console.log("Sending command:", message.utf8Data);
						const esp32con = connections_esp32["TEAM"+connection.team_number];

						const shortened = JSON.parse(message.utf8Data);
						const shortenedInput = shortened.input;

						if (shortenedInput.key == "L-X" || shortenedInput.key == "L-Y" || shortenedInput.key == "R-X" || shortenedInput.key == "R-Y") {
							console.log("Ignoring joystick")
						} else {
							console.log("Received command for team:", messageParsed);
							console.log("Sending command:", message.utf8Data);
							const extraShortened = {
								key: shortenedInput.key,
								value: shortenedInput.value,
								seq: shortened.sequenceNumber
							};

							esp32con.sendUTF(JSON.stringify(extraShortened));
							// handle acknowledgements
							//ack_intervals[connection.team_number + '-' + shortened.sequenceNumber] = setInterval(() => {
							//	// resend the message
							//	console.log(`Re-sending ACK for team: ${connection.team_number} @ seq: ${shortened.sequenceNumber}`);
							//	esp32con.sendUTF(JSON.stringify(extraShortened));
							//}, 1000);
						}
					} else {
						console.log("NOT Sending command because no ESP32 is registered for team:", connection.team_number);
					}
				} else if (isNumeric(message.utf8Data)) {
					console.log(`Received acknowledgement for team ${connection.team_number}: ${message.utf8Data}`);
					//const sequenceNumber = message.utf8Data;
					//clearInterval(ack_intervals[connection.team_number + '-' + sequenceNumber]);
					//delete ack_intervals[connection.team_number + '-' + sequenceNumber];
				}

			} catch (error) {
				console.error("Caught error", error);
			}

			// sending ECHO
            //connection.sendUTF(message.utf8Data);
        } else if (message.type === 'binary') {
            console.log('Received Binary Message of ' + message.binaryData.length + ' bytes');

			// sending ECHO
            connection.sendBytes(message.binaryData);
        } else {
			console.error('Unknown message type!');
		}
    });

    connection.on('close', function(reasonCode, description) {
        console.log((new Date()) + ' Peer ' + connection.remoteAddress + ' disconnected.');
    });

	/*
    setTimeout(() => {
    setInterval(() => {
	connection.sendUTF("FLASH_ON");
    }, 1000);
    }, 500);
    setInterval(() => {
	connection.sendUTF("FLASH_OFF");
    }, 1000);
	*/
});
