# UNT Robotics Discord Bot

An interactive **gateway** Discord bot (discord.js v14) for the UNT Robotics
server. Unlike the existing REST-only PHP bot (`api/discord/bots/admin.php`),
this is an always-on Node service that holds a live gateway connection so it can
respond to slash commands, welcome new members, auto-respond to keywords, and —
the headline feature — **verify members' UNT email addresses** and grant them the
**Verified UNT** role.

It runs as its own Kubernetes pod (`k8s/base/discord-bot.yaml`), **reuses the
existing admin bot token**, and shares the app's MySQL database + SendGrid key.

> The PHP bot is unchanged. This service only *matches* its IDs and conventions.

---

## Features

| Feature | Where | Notes |
| --- | --- | --- |
| Slash command framework | `src/commands/*`, `src/lib/register.js` | `/ping`, `/members`, `/verify`, `/token`. Drop a new file in `src/commands/` and re-run `npm run register`. |
| Welcome + member stats | `src/events/guildMemberAdd.js`, `src/commands/members.js` | DMs a welcome (falls back to the verify channel); `/members` shows total + online. |
| Keyword auto-responses | `src/events/messageCreate.js`, `src/lib/autoresponses.js` | Configurable substring→reply map. Ignores bots/itself. |
| Auto-reactions | same | Optional per-channel emoji map (`CHANNEL_REACTIONS`, empty by default). |
| **UNT email verification** | `src/commands/verify.js`, `src/commands/token.js`, `src/lib/verification.js` | `/verify <email>` → emailed code → `/token <code>` → role granted. All replies ephemeral. |

---

## Project layout

```
discord-bot/
├─ package.json            deps: discord.js, mysql2, @sendgrid/mail (+dotenv dev)
├─ Dockerfile              node:20-alpine, npm ci --omit=dev
├─ .env.example            copy to .env for local dev
└─ src/
   ├─ index.js             Client + intents, wires events, login, graceful shutdown
   ├─ config.js            all env config + fail-fast validation
   ├─ db.js                mysql2/promise pool
   ├─ logger.js            tiny timestamped logger
   ├─ commands/            ping.js, members.js, verify.js, token.js, index.js (loader)
   ├─ events/              ready, guildMemberAdd, messageCreate, interactionCreate, index.js (loader)
   └─ lib/
      ├─ verification.js   the verification state machine (rate limits, hashing, lockout)
      ├─ email.js          SendGrid wrapper
      ├─ ratelimit.js      in-memory per-attempt cooldown guard
      ├─ autoresponses.js  keyword + reaction config
      └─ register.js       `npm run register` — pushes guild slash commands
```

---

## Configuration

All config is read from the environment (`src/config.js`). In Kubernetes most of
it arrives via `envFrom: web-config + web-secrets` (the same ConfigMap/Secret the
PHP app uses); `config.js` accepts the app's existing variable names as
fall-backs so nothing has to be duplicated.

### Required (the bot crash-loops if any are missing)

| Bot var | Falls back to | Source today |
| --- | --- | --- |
| `DISCORD_BOT_TOKEN` | `DISCORD_ADMIN_BOT_TOKEN` | **exists** in `web-secrets` |
| `DISCORD_CLIENT_ID` | `DISCORD_APP_CLIENT_ID` | **exists** in `web-secrets` |
| `DISCORD_GUILD_ID` | — (default `639209564188704768`) | literal in `discord-bot.yaml` |
| `DISCORD_VERIFIED_ROLE_ID` | — | **must be added** (literal in `discord-bot.yaml`) |
| `DISCORD_VERIFY_CHANNEL_ID` | — | **must be added** (literal in `discord-bot.yaml`) |
| `SENDGRID_API_KEY` | — | **exists** in `web-secrets` |
| `DB_PASSWORD` | `DATABASE_PASSWORD` | **exists** in `web-secrets` |

### Optional / defaulted

| Var | Default | Meaning |
| --- | --- | --- |
| `DISCORD_LOG_CHANNEL_ID` | `674703370971250708` (admin channel) | where audit lines post |
| `ALLOWED_EMAIL_DOMAINS` | `unt.edu,my.unt.edu,untsystem.edu,untdallas.edu,unthsc.edu` | exact-domain allow-list |
| `EMAIL_FROM` / `EMAIL_FROM_NAME` | `verify@untrobotics.com` / `UNT Robotics` | SendGrid sender (must be a verified sender) |
| `DB_HOST` / `DB_USER` / `DB_NAME` | `mysql` / `untrobotics-web` / `untrobotics` (or `DATABASE_*`) | DB connection |
| `CODE_LENGTH` | `6` | digits in the code |
| `CODE_TTL_SECONDS` | `600` | code lifetime |
| `MAX_VERIFY_PER_HOUR` | `5` | code sends per user per hour |
| `VERIFY_COOLDOWN_SECONDS` | `60` | min seconds between sends |
| `MAX_TOKEN_ATTEMPTS` | `5` | wrong guesses before a code is burned |
| `TOKEN_ATTEMPT_COOLDOWN_SECONDS` | `5` | min seconds between `/token` tries |
| `LOCKOUT_THRESHOLD` | `10` | lifetime failed guesses before lockout |
| `LOCKOUT_SECONDS` | `3600` | lockout duration |
| `VERIFY_HASH_SECRET` | falls back to `HASH_SALT` | mixed into the code hash |

**No new secrets are required** — the token, client id, SendGrid key and DB
password already exist in `web-secrets`. The only *new* values are the
**Verified UNT role id** and the **verify channel id**, which are non-secret and
live as literals in `k8s/base/discord-bot.yaml`.

---

## Database

A new table `discord_verifications` is added to
`docker/mysql/initdb/01-schema.sql`. One row per Discord user; the plaintext
code is **never** stored (only `code_hash = sha256(code + discord_id + secret)`).

```sql
CREATE TABLE `discord_verifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `discord_id` bigint(20) NOT NULL,          -- UNIQUE: one row per user
  `email` varchar(255) DEFAULT NULL,         -- UNIQUE: an email binds to one user
  `code_hash` varchar(64) DEFAULT NULL,      -- sha256 hex; NULL once burned/used
  `expires_at` datetime DEFAULT NULL,
  `attempts` int(11) NOT NULL DEFAULT '0',   -- wrong guesses for the CURRENT code
  `verified` tinyint(1) NOT NULL DEFAULT '0',
  `verified_at` datetime DEFAULT NULL,
  `last_sent_at` datetime DEFAULT NULL,      -- for the per-send cooldown
  `send_count_window` int(11) NOT NULL DEFAULT '0',  -- sends in the current hour window
  `window_started_at` datetime DEFAULT NULL,
  `failed_total` int(11) NOT NULL DEFAULT '0',       -- lifetime wrong guesses (lockout)
  `locked_until` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `discord_id` (`discord_id`),
  UNIQUE KEY `email` (`email`),
  KEY `verified` (`verified`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
```

> `01-schema.sql` only runs on a **fresh** MySQL data dir. For an existing
> database, run the `CREATE TABLE` above by hand once.

---

## Verification flow

```
/verify yourEUID@unt.edu      (in the verify channel, reply is ephemeral)
   ├─ validate the email is a valid address AND an exact allowed UNT domain
   ├─ reject if already verified, or if the email belongs to another user
   ├─ rate-limit: VERIFY_COOLDOWN_SECONDS between sends, MAX_VERIFY_PER_HOUR/hour
   ├─ generate a CODE_LENGTH-digit code (crypto.randomInt, zero-padded)
   ├─ email the code via SendGrid (expires in CODE_TTL_SECONDS)
   └─ store sha256(code+discord_id+secret), expires_at, reset attempts
        → ephemeral "check your email" (code never echoed in Discord)

/token 123456                 (in the verify channel, reply is ephemeral)
   ├─ reject if no request / already verified / locked / code expired
   ├─ enforce TOKEN_ATTEMPT_COOLDOWN_SECONDS between attempts
   ├─ constant-time hash compare (crypto.timingSafeEqual)
   ├─ WRONG  → attempts++, failed_total++; burn the code at MAX_TOKEN_ATTEMPTS;
   │           lock for LOCKOUT_SECONDS at LOCKOUT_THRESHOLD; generic error msg
   └─ RIGHT  → verified=1, grant the Verified UNT role, ephemeral success,
               audit line posted to the log channel
```

### Why this is hard to brute-force (and a note on 4 vs 6 digits)

A short numeric code is only safe because **many** controls stack:

- **6-digit code** = 1,000,000 possibilities.
- **`MAX_TOKEN_ATTEMPTS = 5` per code**, then the code is burned and a fresh
  `/verify` is required. So each emailed code gives an attacker only 5 of those
  1,000,000 guesses — ~1-in-200,000 odds per code.
- **Codes expire** (`CODE_TTL_SECONDS`), so guesses are also racing a clock.
- **Send rate limits** (`VERIFY_COOLDOWN_SECONDS`, `MAX_VERIFY_PER_HOUR`) cap how
  fast an attacker can obtain fresh codes — at most 5 codes/hour = 25 guesses/hr.
- **Per-attempt cooldown** (`TOKEN_ATTEMPT_COOLDOWN_SECONDS`) throttles guessing.
- **Global lockout** (`LOCKOUT_THRESHOLD` lifetime fails → `LOCKOUT_SECONDS`
  freeze) stops sustained attacks outright.

**4 digits is materially weaker.** With a 4-digit code there are only 10,000
possibilities and, at 5 attempts per code, an attacker expects a hit after
~2,000 fresh codes. At 4 digits it's the **send-rate limit + lockout** doing all
the work — guessing the code itself is no longer a meaningful barrier. **6 digits
is strongly recommended**: it's one extra character of UX for ~100× the keyspace.
`CODE_LENGTH` is configurable, but the default is (and should stay) **6**.

> Because the bot runs as a single instance, the short per-attempt/per-send
> cooldowns are enforced in memory (`src/lib/ratelimit.js`); the durable limits
> (hourly window, lockout, expiry, attempt counts) live in the DB row and
> survive restarts.

---

## Local development

```bash
cd discord-bot
cp .env.example .env        # fill in token, client id, role/channel ids, SendGrid, DB
npm install
npm run register            # push slash commands to the guild (instant)
npm start
```

You need a reachable MySQL with the `discord_verifications` table and a valid
SendGrid sender. Point `DB_HOST` at your dev DB.

---

## Discord Developer Portal + server setup (do this once)

**Developer Portal** (<https://discord.com/developers/applications> → your app):

1. **Bot → Privileged Gateway Intents**: enable **Message Content Intent** and
   **Server Members Intent**. (Presence Intent is *not* required — `/members`
   uses an approximate count.)
2. **OAuth2 → URL Generator**: scopes **`bot`** + **`applications.commands`**;
   bot permissions **Manage Roles**, **Send Messages**, **Read Message History**
   (and **Add Reactions** if you use auto-reactions). Invite the bot with that
   URL (it's the same application as the admin bot, so it may already be in the
   server — you still need the intents + permissions above).

**Server setup:**

3. Create the **Verified UNT** role. Copy its id → `DISCORD_VERIFIED_ROLE_ID`.
4. Create (or pick) the **verify channel**. Copy its id → `DISCORD_VERIFY_CHANNEL_ID`.
5. **Channel permissions so unverified users only see the verify channel:**
   - On **@everyone**: deny **View Channel** server-wide (or on each category).
   - On the **verify channel**: allow **@everyone** to View Channel + Send
     Messages + Use Application Commands.
   - On the **Verified UNT** role: allow **View Channel** for the channels/
     categories members should see after verifying.
   - (Optional) on the verify channel, deny **View Channel** for **Verified UNT**
     so it disappears once they're done.
6. **Role hierarchy:** drag the **bot's role above** the **Verified UNT** role in
   Server Settings → Roles. A bot can only assign roles **below** its own highest
   role — if it's not above, `/token` will succeed in the DB but fail to grant the
   role (the bot will tell the user to ping an officer and log it).
7. Enable **Developer Mode** (User Settings → Advanced) to right-click → *Copy ID*
   for roles/channels.

---

## Registering slash commands

```bash
npm run register
```

Pushes the four commands as **guild** commands to `DISCORD_GUILD_ID` (instant
propagation). Re-run whenever you add/rename a command or change its options.
This is a one-shot script — it does not open a gateway connection. It can run
locally or as a one-off job in the cluster (same env).

---

## Test plan

**Happy path**
1. Join the server with a test account → expect a welcome DM (or a post in the
   verify channel if DMs are closed) mentioning the verify channel.
2. `/ping` → ephemeral pong with latency. `/members` → ephemeral count.
3. `/verify yourEUID@unt.edu` in the verify channel → ephemeral "check your
   email"; the code arrives by email and is **not** shown in Discord.
4. `/token <code>` → ephemeral success; **Verified UNT** role appears; an audit
   line posts to the log channel.

**Validation / negatives**
5. `/verify foo@gmail.com` → rejected (bad domain). `/verify notunt.edu...` style
   substring tricks → rejected (exact-domain match only).
6. `/verify` or `/token` in a **non-verify** channel → ephemeral "use it in
   #verify", nothing leaks.
7. Re-run `/verify` immediately → "wait Ns" cooldown. Run it >5× in an hour →
   hourly-limit message.
8. Have a **second** account `/verify` the **same** email → rejected
   (email already linked).
9. Already-verified user runs `/verify` or `/token` → "already verified".

**Brute-force / lockout**
10. `/token` with wrong codes: each try is throttled
    (`TOKEN_ATTEMPT_COOLDOWN_SECONDS`); the message stays **generic** ("invalid or
    expired") and shows attempts remaining. After `MAX_TOKEN_ATTEMPTS` the code is
    **burned** — a correct guess afterward still fails and a new `/verify` is
    required.
11. Keep failing across codes until `failed_total` hits `LOCKOUT_THRESHOLD` →
    `/token` is locked for `LOCKOUT_SECONDS`; a `:lock:` audit line posts.
12. Let a code sit past `CODE_TTL_SECONDS` → `/token` reports the generic
    invalid/expired message.

**Keyword / reactions**
13. Post a message containing a configured trigger (e.g. "how do i verify") →
    bot replies. Bot ignores its own/other bots' messages. Add a channel to
    `CHANNEL_REACTIONS` and confirm the emoji is added.

**Ops**
14. Kill the pod / `kubectl delete pod` → it restarts and re-touches the
    heartbeat file (liveness probe stays green). DB-backed limits persist across
    the restart.

---

## CI / build / kustomize wiring (must be added — not done here)

This bot needs to be added to three places. **Exact additions:**

**1. `.github/workflows/build-images.yml`** — add a third matrix entry under
`strategy.matrix.include:`

```yaml
          - name: website-discord-bot
            context: discord-bot
            dockerfile: discord-bot/Dockerfile
```

**2. `deploy/build-and-load-images.sh`** — build + import the image. After the
driver-ws build block add:

```bash
echo "==> Building untrobotics-discord-bot:dev"
docker build -t untrobotics-discord-bot:dev -f discord-bot/Dockerfile discord-bot
```

and in the import section add:

```bash
docker save untrobotics-discord-bot:dev | sudo k3s ctr images import -
```

(optionally widen the final `grep -E 'untrobotics-(web|driver-ws):dev'` to
`untrobotics-(web|driver-ws|discord-bot):dev`.)

**3. `k8s/base/kustomization.yaml`** — add the manifest to `resources:` and the
image to `images:`

```yaml
resources:
  - config.yaml
  - mysql.yaml
  - web.yaml
  - driver-ws.yaml
  - discord-bot.yaml          # <-- add
images:
  - name: untrobotics-web
    newTag: dev
  - name: untrobotics-driver-ws
    newTag: dev
  - name: untrobotics-discord-bot   # <-- add
    newTag: dev
```

Then build/load and scale up in exactly one environment:

```bash
bash deploy/build-and-load-images.sh
kubectl -n untrobotics apply -k k8s/overlays/<env>
kubectl -n untrobotics scale deploy/discord-bot --replicas=1
```

---

## Residual TODOs

- [ ] Fill the real **Verified UNT role id** and **verify channel id** into
      `k8s/base/discord-bot.yaml` (placeholders `REPLACE_WITH_*`).
- [ ] Do the three CI/build/kustomize edits above (intentionally left to you).
- [ ] Apply the `discord_verifications` `CREATE TABLE` to the **existing** prod
      DB (init script only runs on a fresh data dir).
- [ ] Confirm `EMAIL_FROM` (`verify@untrobotics.com`) is a **verified sender /
      authenticated domain** in SendGrid, or change it to one that is.
- [ ] Enable the two privileged intents + invite/permission the bot per the
      setup steps, and ensure the bot role sits **above** Verified UNT.
- [ ] Run `npm run register` once (and after any command changes).
- [ ] Optional: tune the keyword/reaction maps in `src/lib/autoresponses.js`.
- [ ] `package-lock.json` is not committed; generate one (`npm install`) so the
      Docker build can use `npm ci` for reproducible installs.
```
