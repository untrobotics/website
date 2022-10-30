const fs = require('node:fs');
const path = require('node:path');


const { Client, Events, GatewayIntentBits, Partials, Collection} = require('discord.js');

//will someone please tell me how to typescript/jsdoc json properly?
/*
 * @type {any} watchableMessages
 * @type {string} watchableMessages.id
 * @type {string} watchableMessages.channelId
 * @type {string} watchableMessages.guildId
 * @type {[x: number]} watchableMessages.emojiIds
*/
const { token, watchableMessages, emojis, clientId } = require('./config.json');
const client = new Client({
    intents: [GatewayIntentBits.Guilds, GatewayIntentBits.GuildMessageReactions, GatewayIntentBits.GuildMembers],
    partials: [Partials.Message, Partials.Reaction],
});

/**
 * @type {Collection<import("discord.js").Snowflake,Guild>} A cached collection of the guilds the bot is in
 */
const guilds = client.guilds.cache;

//Values should Look like <guildId, <emojiId, Role>>
                                //Role is the discord object Role, the rest are a string representing the id
/**
 * Takes data from config.json. Values should Look like <guildId, <emojiId, Role>>. Role is the discord object Role, the rest are a string or snowflake representing the id
 * @type {Map<string, Map<string|import("discord.js").Snowflake,import("discord.js").Role>>} A map that takes the id of the guild and maps it to another map whose key is the emoji's id and value is the Role object from Discord.JS
 */
emojiToRole = new Map;

client.on("ready", OnReady());

client.on(Events.MessageReactionAdd, OnReactionAdd());


client.on(Events.MessageReactionRemove, OnReactionRemove())

client.once(Events.ClientReady, c => {
    console.log(`Ready! Logged in as ${c.user.tag}`);
});

// Log in to Discord with your client's token
client.login(token);

//not documenting the listeners because they shouldn't need to be modified (unless something breaks)

function OnReady() {
    return () => {
        emojis.forEach((emojiItem) => {
            let guild = client.guilds.cache.get(emojiItem.guildId);
            if (guild == null /*|| guild.roles.fetch(emojiItem.roleId)==null*/) //make sure the guild exists and that it has the role we need to check
                //we do not check for the emoji because nitro users exist :face_vomiting:
            {
                console.log(`couldn't find guild with specified id ${emojiItem.guildId}`)
                return;
            }
            guild.roles.fetch(emojiItem.roleId).then((roleToAdd) => {
                if (emojiToRole.has(emojiItem.guildId)) //if the guild has already been added to the map
                    //we need to get the first map to set the nested map so we can add a new value in it
                    emojiToRole.get(emojiItem.guildId).set(emojiItem.id, roleToAdd);
                else //we haven't added the guild to the map and we can use set
                    emojiToRole.set(emojiItem.guildId, new Map([[emojiItem.id, roleToAdd]]));
            }, () => {
                console.log(`Couldn't find a role in the guild with the id ${emojiItem.roleId}, or had issues with fetching`)
            })

        })
    };
}

function OnReactionAdd() {
    return async (reaction, user) => {
        // When a reaction is received, check if the structure is partial, most likely yes for the 1st time because we didn't fetch in ready
        if (reaction.partial) {
            // If the message this reaction belongs to was removed, the fetching might result in an API error which should be handled
            try {
                await reaction.fetch(); //i don't understand if this updates the reaction variable or if this does anything (the tutorial for discord.js had this line as-is)
            } catch (error) {
                console.error('Something went wrong when fetching the message:', error);
                // Return because `reaction.message.author` may be undefined/null
                return;
            }
        }
        if (/*!watchableMessages.includes(reaction.message.id)*/watchableMessagesHasVal(reaction.message.id, 'id')) {
            console.log(`Not right message id ${reaction.message.id}`);
            return;
        }
        if (!hasEmoji(reaction.emoji)) {
            console.log(`Could not find emoji with ID ${reaction.emoji.id}.`);
            return;
        }
        if(user.id==clientId)
            return;
        reaction.message.guild.members.fetch(user.id).then((guildMember) => {
            try {
                guildMember.roles.add(emojiToRole.get(reaction.message.guildId).get(reaction.emoji.id));
            }catch (e) {
                console.error(`Error trying to add role to user:`,e);
            }
        }, (err) => {
            console.error(`Something went wrong with finding the guildMember:`,err);
        });
    };
}

function OnReactionRemove() {
    return async (reaction, user) => {
        // When a reaction is received, check if the structure is partial (what?)
        if (reaction.partial) {
            // If the message this reaction belongs to was removed, the fetching might result in an API error which should be handled
            try {
                await reaction.fetch();
            } catch (error) {
                console.error('Something went wrong when fetching the message:', error);
                // Return as `reaction.message.author` may be undefined/null
                return;
            }
        }
        if (/*!watchableMessages.includes(reaction.message.id)*/watchableMessagesHasVal(reaction.message.id, 'id')) {
            console.log(`Not right message id ${reaction.message.id}`);
            return;
        }
        if (!hasEmoji(reaction.emoji)) {
            console.log(`Could not find emoji with ID ${reaction.emoji.id}.`);
            return;
        }
        reaction.message.guild.members.fetch(user.id).then((guildMember) => {
            try {
                guildMember.roles.remove(emojiToRole.get(reaction.message.guildId).get(reaction.emoji.id));
            } catch (e) {
                console.error(`Error trying to add role to user:`,e);
            }
        }, (err) => {
            console.error(`Something went wrong with finding the guildMember:`, err);
        });
    };
}

/**
 * @param {import("discord.js").Emoji} emoji The Emoji object of the emoji you want to confirm its existence in the array
 * @param {string|import("discord.js").Snowflake} emoji.id The id of the emoji, either a string or 'Snowflake' as Discord calls it (both are still comparable)
 * @returns {boolean} Whether the emoji is in the emojis JSON array
 */
function hasEmoji(emoji)
{return !(emojis.every((item)=> {if(emoji.id.toString()==item.id) return false;}))}

/**
 * @param {import("discord.js").Snowflake|string} value The value we want to find in the watchableMessages array
 * @param {string} valueType The name of the property from watchableMessages we are comparing to
 * @returns {boolean} Whether the watchableMessages array has an element with a property with the same value as value
 */
function watchableMessagesHasVal(value, valueType)
{
    if(valueType==='id')
        watchableMessages.forEach((message)=>
        {
            if(message.id==value)
                return true;
        })
    else if(valueType==='channelId')
        watchableMessages.forEach((message)=>
        {
            if(message.channelId==value)
                return true;
        })
    else if(valueType==='guildId')
        watchableMessages.forEach((message)=>
        {
            if(message.guildId==value)
                return true;
        })
    return false;
}