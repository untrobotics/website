# DiscordBotReactionRolesJS
A Discord bot using Discord.JS made to add a role based off of emojis on specific messages

# Usage

A secondary file is needed, config.json (along with the discord.js packages).

An example of how you should format it:

```json
{
  "token": "tokenID",
  "clientId": "clientID",
  "watchableMessages": [
    {
      "id":"messageID",
      "channelId": "channelID",
      "guildId": "guildID",
      "emojiIds": ["emojiID1", "emojiID2", "emojiID3"]
    }],
  "emojis" : [
    {
      "name": "emoji name",
      "id":"emojiID",
      "guildId": "guildID",
      "roleId": "roleID",
      "roleName": "roleName",
      "messageIds": ["messageID1", "messageID2", "messageID3"]
    }
  ]
}
```

Most of these are self-explanatory, just enter the values as you would if it were a normal variable or parameter in discord.js.

`watchableMessages.emojiIds` is an array of the emoji IDs that need to be checked (i.e. these emojis will correspond to a role). Limit would be whatever the limit JSON or JavaScript puts on array lengths.

`emojis.name` is the name of the emoji as it appears in discord. This property isn't used in the code, it's mainly for readability. Same with `emojis.roleName`.

`emojis.guildId` is the guild you're checking the emoji for. And so therefore `emojis.messageIds` must all be IDs from the same server. Will fix that one day.

Both `emojis` and `watchableMessages` are arrays so you can add multiple things to watch. 
