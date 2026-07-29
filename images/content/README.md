# Site content media

Curated photos/videos for the site, sourced from the UNT Robotics Google Drive
(public files) and the Discord archive (via the bot). Optimized to <=1920px, q82.

- `fetch-drive-media.sh` — pulls public Drive images via the thumbnail endpoint.
- `discord-refetch.js` — re-signs Discord CDN URLs via the bot (needs DISCORD_TOKEN)
  and downloads. Discord CDN links expire, so re-run this (not the raw URLs) to refresh.

Folders: aerospace (rocketry/NASA SL), rover, scrappe, sofabot, printing,
botathon, events (HackUNT/meetings), outreach, rec, ieee2019, video.
