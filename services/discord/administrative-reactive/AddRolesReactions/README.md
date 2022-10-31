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

`emojis.name` is the name of the emoji as it appears in discord. This property isn't used in the code, it's mainly for readability. Same with `emojis.roleName`.

`emojis.guildId` is the guild you're checking the emoji for. And so therefore `emojis.messageIds` must all be IDs from the same server. Will fix that one day.

Both `emojis` and `watchableMessages` are arrays so you can add multiple things to watch. 

If the emoji is a unicode emoji (e.g. :rocket: or :thumbsup: in Discord), then have emojis.id as `null` and the name as the literal name (e.g. "rocket" or "thumbsup", no colons, and not the unicode representation).
