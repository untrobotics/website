#!/usr/bin/env node
// noinspection DuplicatedCode,ES6ConvertVarToLetConst

var WebSocketServer = require('websocket').server;
var http = require('http');
//var https = require('https');
//var fs = require('fs');

var server = http.createServer(
//{
//      key: fs.readFileSync( '/etc/letsencrypt/live/www.untrobotics.com/privkey.pem' ),
//      cert: fs.readFileSync( '/etc/letsencrypt/live/www.untrobotics.com/cert.pem' )
//},
    function (request, response) {
        console.log((new Date()) + ' Received request for ' + request.url);
        response.writeHead(404);
        response.end();
    });

server.listen(81, function () {
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

function originIsAllowed(origin) {
    // put logic here to detect whether the specified origin is allowed.
    return true;
}

function IsJsonString(str) {
    try {
        if (!isNaN(str)) {
            return false;
        }
        JSON.parse(str);
    } catch (e) {
        return false;
    }
    return true;
}

const resendDelay = 200 //ms
const commandQueue = {}
const commandReceivedPromises = {}
const acknowledgeReceivedPromises = {}

async function sendCommand(teamNumber) {
    const connection = connections_esp32['TEAM' + teamNumber]
    while (true) {
        if (commandQueue[teamNumber].length === 0) {
            await new Promise(resolve => {
                commandReceivedPromises[teamNumber] = resolve
            })
        }
        let message = JSON.stringify(commandQueue[teamNumber][0])
        while (true) {
            connection.sendUTF(message)
            if (await new Promise((resolve, reject) => {
                acknowledgeReceivedPromises[teamNumber] = resolve
                setTimeout(reject, resendDelay)
            }).then(() => {
                return true
            }).catch(() => {
                return false
            })) {
                commandQueue[teamNumber].shift()
                break;
            }
        }
    }
}

const connections_esp32 = {};

function createQueue(teamNumber) {
    commandQueue[teamNumber] = []
    commandReceivedPromises[teamNumber] = new function () {
    }
    acknowledgeReceivedPromises[teamNumber] = new function () {
    }
    sendCommand(teamNumber)
}

wsServer.on('request', function (request) {
    if (!originIsAllowed(request.origin)) {
        // Make sure we only accept requests from an allowed origin
        request.reject();
        console.log((new Date()) + ' Connection from origin ' + request.origin + ' rejected.');
        return;
    }

    var connection = request.accept('team');
    console.log((new Date()) + ' Connection accepted.');

    connection.on('message', function (message) {
        if (message.type === 'utf8') {
            console.log('Received Message: ', message.utf8Data);

            try {

                if (message.utf8Data.startsWith("ESP32_TEAM_")) {
                    console.log("Validate ESP32:", message.utf8Data);
                    const teamNumber = message.utf8Data.replace("ESP32_TEAM_", "");
                    console.log("TEAM ESP32", teamNumber);

                    connections_esp32["TEAM" + teamNumber] = connection;
                    connection.team_number = teamNumber
                } else if (message.utf8Data.startsWith('^')) {
                    if (commandQueue[connection.team_number][0].sequenceNumber === parseInt(message.utf8Data.substring(1)))
                        acknowledgeReceivedPromises[connection.team_number]()
                    else
                        console.log(`Ignoring acknowledgement for sequence number ${message.utf8Data.substring(1)} because current sequence number is ${commandQueue[connection.team_number][0].sequenceNumber}`)
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
                            createQueue(teamNumber)
                        }
                    }

                    // command
                    console.log("Received command for team:", messageParsed);
                    if (connections_esp32["TEAM" + connection.team_number]) {
                        /*console.log("Sending command:", message.utf8Data);
                        const esp32con = connections_esp32["TEAM" + connection.team_number];
                        esp32con.sendUTF(message.utf8Data);*/
                        console.log('Adding command to queue:', message.utf8Data)
                        commandQueue[connection.team_number].push(messageParsed)
                        commandReceivedPromises[connection.team_number]()
                    } else {
                        console.log("NOT Sending command because no ESP32 is registered for team:", connection.team_number);
                    }
                }

            } catch (error) {
                console.error("Caught error", error);
            }

            // sending ECHO
            connection.sendUTF(message.utf8Data);
        } else if (message.type === 'binary') {
            console.log('Received Binary Message of ' + message.binaryData.length + ' bytes');

            // sending ECHO
            connection.sendBytes(message.binaryData);
        } else {
            console.error('Unknown message type!');
        }
    });

    connection.on('close', function (reasonCode, description) {
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
