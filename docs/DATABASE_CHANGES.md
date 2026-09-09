# Making database changes in a pull request

Every schema change ships as a **forward-only migration file** in
`sql/migrations/`, applied by `sql/migrate.php`. Don't run ad-hoc `ALTER`s
against a database by hand — a change that isn't a committed migration will be
missing on the next environment (and lost when a pod restarts). Put it in a
migration and it applies itself, everywhere, in order.

## TL;DR

1. Add `sql/migrations/YYYYMMDD-short-description.sql` with your SQL.
2. Commit it in the **same branch/PR** as the code that needs it.
3. That's it. **There is no manual step in any environment.** It applies itself
   on the next deploy: to **dev** when your branch merges to `develop`, to
   **prod** at the release to `master`.

## How to add a migration

Create a file under `sql/migrations/` named with a **sortable timestamp prefix**
so migrations apply in the order they were written:

```
sql/migrations/20260909-add-widget-table.sql
```

Start it with a short comment saying why (and the ticket, by convention), then
the SQL. A file may contain **multiple statements** separated by `;` — the
runner executes them all:

```sql
-- URW-123: widgets need an owner + an index for the dashboard query.
ALTER TABLE `widgets`
  ADD COLUMN `owner_uid` int(11) DEFAULT NULL,
  ADD KEY `idx_widgets_owner` (`owner_uid`);
```

Real example from this repo — `20260909-dyndns-keys-per-member.sql`:

```sql
-- URW-242: attach dyndns keys to members. UNIQUE(uid) = one key per member;
-- MySQL allows multiple NULLs, so global keys (uid NULL) still coexist.
ALTER TABLE `dyndns_api_keys`
  ADD COLUMN `uid` int(11) DEFAULT NULL,
  ADD UNIQUE KEY `uq_dyndns_uid` (`uid`);
```

Then commit the migration alongside the code change that depends on it, so the
schema and the code that uses it land together.

## How it runs

`sql/migrate.php` is a small forward-only runner:

- It records every applied file in a `schema_migrations` table and applies each
  `sql/migrations/*.sql` that isn't recorded yet, in **ascending filename order**.
- It's **lock-guarded** (`GET_LOCK`), so several pods starting at once can't
  apply the same file twice.
- On every deploy it runs in the **`migrate` init container** (see
  `k8s/base/web.yaml`) **before** the web container serves traffic. A migration
  that fails **blocks the rollout** rather than letting the app serve on a
  half-migrated schema. See [`../deploy/cicd.md`](../deploy/cicd.md) for how a
  push becomes a deploy.

So the lifecycle of a migration is: merge to `develop` → it applies to **dev** on
that rollout; release `develop`→`master` → it applies to **prod** on that
rollout.

## Commands (you don't need these to merge a PR)

**None of these are part of the normal flow** — the init container applies
migrations for you on every deploy. They're only for local Docker dev, checking
what's applied, or the one-time baseline:

```sh
php sql/migrate.php           # apply pending migrations — this is what the init container runs for you
php sql/migrate.php status    # list applied vs pending, then exit (read-only)
php sql/migrate.php baseline   # record ALL current files as applied WITHOUT running them
```

- In the **Docker Compose dev stack**, PHP is in the container and migrations
  also apply on container start; `php sql/migrate.php` is there if you want to
  run or inspect manually.
- On **dev2 / prod** you never run it by hand — but to *check* status you can
  exec into a pod (there's no local PHP binary on the dev machine):

  ```sh
  ssh ubuntu@dev2.untrobotics.com \
    "sudo kubectl exec -n untrobotics-dev deploy/web -c web -- php /var/www/html/sql/migrate.php status"
  ```

- Use `baseline` **once** on a pre-existing database already at the current
  schema (e.g. when first adopting the tool), so historical migrations aren't
  re-run.

## Rules & gotchas

- **Forward-only — no down migrations.** To undo something, write a *new*
  migration that reverses it.
- **Never edit a migration once it's merged/applied.** It's tracked by filename,
  so an edit won't re-run — and other environments already ran the old content.
  Add a new migration instead.
- **Prefer statements that are safe to re-run.** If a migration fails partway,
  the file isn't recorded, so the next deploy retries it *from the top*. Keep a
  file to one logical change, and where practical use guarded DDL
  (`ADD COLUMN IF NOT EXISTS`, `DROP ... IF EXISTS`, `CREATE TABLE IF NOT
  EXISTS`) so a retry is clean.
- **New tables:** also add the `CREATE TABLE` to `docker/mysql/initdb/01-schema.sql`
  so a freshly-provisioned dev database has it from the start. The migration is
  what updates *existing* databases; the initdb schema seeds *new* ones.
- **Keep app code tolerant across the boundary.** The init container migrates
  before the new code serves, so within a single rollout they're effectively
  atomic — but don't write code that would hard-fail against the *old* schema if
  it happens to run first.

## Related

- [`../deploy/cicd.md`](../deploy/cicd.md) — how pushes/releases become deploys
  (and thus when migrations run).
- `sql/migrate.php` — the runner itself; its header documents the modes.
