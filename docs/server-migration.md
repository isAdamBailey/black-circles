# Spec: Guided migration to a new Forge server (PHP 8.5, Node 24)

## Purpose

The Forge server that hosts Black Circles is reaching end of life and no
longer gets security patches for PHP and Node. A replacement Forge server
already exists, running **PHP 8.5** and **Node 24**. This spec tells an agent
how to walk the owner through moving this app to it with little or no
downtime, and with a way back at every step.

This document is self-contained. Everything needed to set up the site on the
new server is below. Don't rely on other deploy docs in the repo.

## How the app runs in production

```
Browser
  │
  ▼
nginx (one Forge site, one domain)
  ├─ /api/* and /up  →  PHP-FPM  →  Laravel (public/index.php)
  └─ everything else →  proxy_pass 127.0.0.1:3004  →  Node (Nuxt .output/server/index.mjs)
```

- Laravel is a JSON API only. Nuxt 4 is the only UI. It runs as an SPA
  (`ssr: false`), so the Node process only serves the built shell and static
  assets. A Forge **Daemon** (Supervisor) runs it through
  `frontend/deploy/start-nuxt.sh`.
- The site uses Forge **zero-downtime deployments**. Each deploy clones into
  `releases/<id>`, and `$ACTIVATE_RELEASE()` repoints
  `/home/forge/<domain>/current` at the new release. Processes that live
  outside the deploy script, like the Nuxt daemon, must point at `current`.
- Background work: a `database` queue worker, and the Laravel scheduler,
  which runs `discogs:sync` and then `personality:generate` weekly, Sunday
  00:00 America/Los_Angeles.
- Search: Meilisearch on `127.0.0.1:7700` through Laravel Scout. Only
  `App\Models\DiscogsRelease` is searchable.

## Agent role and rules

You are a **guide**. The owner does everything in the Forge UI and on the
servers over SSH. You make the repo changes yourself, but only in Phase 0.

1. **One step at a time.** Give one step, say what success looks like, then
   wait for the owner to report back before moving on.
2. **Check every step with something you can see.** Ask for command output,
   a status in the Forge UI or an HTTP code. "Done" alone is not enough at
   a gate (marked 🚧). Tell the owner the exact command to run and what you
   expect it to print.
3. **Never ask for secrets in chat.** That covers `.env` contents, DB
   passwords, `APP_KEY`, API tokens and the Meilisearch master key. When a
   value has to move from the old server to the new one, have the owner copy
   it directly between Forge tabs. If you need to confirm a secret is set,
   ask for something that doesn't reveal it, such as
   `grep -c '^HUGGINGFACE_API_TOKEN=.\+' .env` → `1`.
4. **The old server stays untouched until Phase 9.** It is the rollback
   path. The only changes to it are turning off push-to-deploy (Phase 2)
   and, after cutover, turning off its scheduler and queue worker.
5. **Forge UI labels may differ** from the ones written here, because Forge
   has redesigned its UI. If the owner can't find something, ask them what
   they see rather than guessing.
6. **Keep a running checklist** of finished phases in the conversation. If
   the session is interrupted, ask which phase was last confirmed and pick up
   from there.
7. When something fails, check the [Troubleshooting](#troubleshooting) table
   first. If that doesn't explain it, stop and diagnose with the owner. Don't
   improvise around the problem.

## What has to move

| Thing | How it moves | Notes |
|---|---|---|
| Code | Git: the new site clones the same repo and branch | Needs the Phase 0 version bump |
| MySQL data | `mysqldump` → import | Small dataset. It only changes during the weekly sync and in Settings |
| `.env` | Copied by hand, Forge Environment tab → Environment tab | Change the DB credentials and Meilisearch values. **Keep `APP_KEY`** |
| Meilisearch index | **Rebuilt from MySQL**, not copied | |
| nginx customization | Reapplied by hand ([nginx config](#nginx-config)) | Forge's default template won't include it |
| Nuxt daemon | Recreated ([Nuxt daemon](#nuxt-daemon)) | The new server gives it a **new daemon id** |
| Queue worker + Scheduler | Recreated in Forge | `database` queue connection |
| SSL certificate | Cloned or reissued (Phase 7) | |
| DNS | A record switched at cutover | |
| `storage/app` | Nothing to move | The app doesn't use `Storage`. Confirm in Phase 1 |

## Inputs to collect first (Phase −1)

Before Phase 0, ask the owner for these and record the answers:

- Production domain (written as `<domain>` below).
- Public IPs of the old server and the new one.
- Where DNS is hosted, whether Cloudflare proxies the record (orange cloud),
  and the A record's current TTL.
- Whether **other sites** on the old server are moving to the new server
  too. That decides whether port `3004` is free (Phase 1).
- Whether Meilisearch is already installed on the new server, and whether
  the old one runs with a master key (`MEILISEARCH_KEY` set in `.env`).
- MySQL version on both servers (`mysql --version`).
- A preferred cutover window. **Avoid Sunday around midnight Pacific**,
  because the weekly sync runs then.

## Phase 0: Repo changes (the agent does these)

Make these on a branch and open a PR. **Do not merge yet.** Phase 2 says
when.

> Status: the branch `chore/php85-node24` already makes these changes.
> On PHP 8.5.12, `composer install`, `check-platform-reqs` and Pest all pass
> (63 tests), with no deprecations. On Node 24.22, `npm ci`, typecheck and
> build all pass. Confirm its PR is still open and CI is green, then skip
> to the gate.

1. **PHP constraint.** `composer.json` has `"php": "~8.3.0"`, which makes
   `composer install` fail its platform check on 8.5. Change it to
   `"php": "^8.3"` so the same lock file installs on the old server (8.3)
   and the new one (8.5) while both exist. Then run `composer update --lock`.
   Tightening to `~8.5.0` comes later (Phase 9).
2. **Check dependencies on 8.5.** Switch Sail to the 8.5 runtime in
   `compose.yaml` (`vendor/laravel/sail/runtimes/8.5`, image `sail-8.5/app`)
   and rebuild with `sail build --no-cache`. Then run `sail composer install`,
   `sail composer check-platform-reqs` and `composer test`. Look in
   `storage/logs` for 8.5 deprecation notices. If a package doesn't support
   8.5, stop and tell the owner before going further.
3. **Node 24.** Under Node 24, run `npm ci && npm run typecheck &&
   npm run build && npm test` in `frontend/`. Also run the root `npm ci`.
   Watch for native modules that rebuild during install.
4. **CI.** In `.github/workflows/lint.yml` and `tests.yml`, set
   `php-version` to `8.5` and `node-version` to `24`, so CI matches
   production.
5. **Docs.** In CLAUDE.md, change "PHP 8.3" to "PHP 8.5".

🚧 Gate: CI is green on the PR, and the owner has reviewed the diff.

## Phase 1: Check the new server

Ask the owner to SSH into the **new** server as `forge` and paste the output
of:

```bash
php -v                       # expect 8.5.x
node -v && npm -v            # expect v24.x
composer --version
mysql --version              # compare with the old server; same major or newer
lsb_release -d
free -h                      # if there's no swap, add it before the first deploy (the Nuxt build can run out of memory)
sudo ss -tlnp | grep -E ':3004|:7700' || echo "3004 and 7700 free"
```

- If **3004 is taken**, maybe by another site already moved, choose a free
  port and use it instead of `3004` everywhere below (daemon Command and both
  nginx `proxy_pass` lines). A port collision is confusing to debug: nginx
  reaches *something*, just not this app.
- If **Meilisearch is missing**, have the owner install it as a systemd
  service listening on `127.0.0.1:7700`, with a master key if the old server
  used one. Confirm with `curl -s 127.0.0.1:7700/health` →
  `{"status":"available"}`.
- On the **old** server, confirm there's nothing to copy:
  `ls -la /home/forge/<domain>/storage/app/{public,private}` should show
  nothing that matters. (The shared `storage/` path may sit under the site
  root or under `current/`. Ask for `ls` output and adapt.)

🚧 Gate: PHP 8.5, Node 24 and Meilisearch are healthy, the port is chosen,
and swap is in place.

## Phase 2: Freeze deploys to the old server and merge Phase 0

1. On the **old** site, turn off **Push to deploy**. Otherwise merging the
   Phase 0 PR deploys to the old server. That deploy would probably work,
   since `^8.3` allows 8.3, but there's no reason to take the risk during a
   migration.
2. Merge the Phase 0 PR.
3. If the DNS TTL is high (over 300s), lower it to 300 now. Wait at least
   the old TTL before the cutover in Phase 8.

## Phase 3: Create the site and database on the new server

1. Create a database and user for the app on the new server (Forge →
   Database). Note the names. The owner keeps the password.
2. Create the site on the new server:
   - Domain: `<domain>`, the same as now.
   - Project type: Laravel/PHP, **PHP 8.5**, web directory `/public`.
   - **Zero-downtime deployments on** (the default for new sites).
   - Same repo and branch as the old site.
   - **Push to deploy off** for now.
3. **Environment.** Have the owner open the old site's Environment tab and
   the new site's side by side and copy everything across. Then change only:
   - `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` → values from step 1.
   - `MEILISEARCH_HOST=http://127.0.0.1:7700`, and `MEILISEARCH_KEY` if the
     new instance has a master key.

   Check these against the old values. They should be the same:
   `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY`,
   `APP_URL=https://<domain>`, `FRONTEND_URL=https://<domain>`,
   `QUEUE_CONNECTION=database`, `SCOUT_DRIVER=meilisearch`,
   `DISCOGS_USERNAME`, `DISCOGS_TOKEN`, `HUGGINGFACE_API_TOKEN`, and
   `VITE_GOOGLE_TAG_ID` (optional, GA4).
4. **Deploy script.** Paste the [deploy script](#deploy-script) below, **but
   comment out the final `sudo supervisorctl restart daemon-<id>:*` line**.
   The daemon doesn't exist yet, so that line would fail the first deploy.

## Phase 4: Copy the data

Do this close to the cutover window, so nothing changes in the gap. The data
only changes during the weekly sync or when someone edits Settings.

1. On the **old** server:
   ```bash
   mysqldump --single-transaction --no-tablespaces --routines \
     -u <old_db_user> -p <old_db_name> | gzip > ~/black-circles-$(date +%F).sql.gz
   ```
2. Move the file to the new server, either by `scp` through the owner's
   machine or by adding the owner's SSH key to both servers in Forge.
3. On the **new** server:
   ```bash
   gunzip -c ~/black-circles-*.sql.gz | mysql -u <new_db_user> -p <new_db_name>
   ```
4. Check row counts **on both servers** and compare them:
   ```bash
   mysql -u <user> -p <db> -e "SELECT 'releases',COUNT(*) FROM discogs_releases UNION ALL SELECT 'items',COUNT(*) FROM discogs_collection_items UNION ALL SELECT 'moods',COUNT(*) FROM moods UNION ALL SELECT 'settings',COUNT(*) FROM settings;"
   ```
   If a table name doesn't match, have the owner run `SHOW TABLES;` and fix
   the query. Don't guess.

🚧 Gate: the counts match.

## Phase 5: First deploy, daemons and nginx

1. Click **Deploy** on the new site and watch the log. `composer install`
   must pass on 8.5 and `npm run build` must pass on Node 24. `migrate
   --force` should report nothing to migrate, because the imported schema is
   current.
2. **Rebuild the search index** (SSH, in `/home/forge/<domain>/current`):
   ```bash
   php artisan scout:sync-index-settings
   php artisan scout:import "App\Models\DiscogsRelease"
   ```
3. **Nuxt daemon.** Create it as described in [Nuxt daemon](#nuxt-daemon).
   Start it once by hand (Supervisor won't start a new daemon on its own)
   and confirm it shows `RUNNING`. Then `curl -sI 127.0.0.1:3004` on the
   server should return `200`. Note the daemon's **new** id, then uncomment
   the `supervisorctl restart daemon-<id>:*` line in the deploy script with
   that id.
4. **Queue worker:** connection `database`, default queue. **Scheduler:**
   enable Forge's Laravel scheduler (`artisan schedule:run` every minute).
5. **nginx.** Apply the [nginx config](#nginx-config). Then run
   `sudo nginx -t && sudo service nginx reload`.
6. Deploy once more, so the daemon restart line is tested.

🚧 Gate: the second deploy succeeds and `sudo supervisorctl status` shows
exactly one Nuxt daemon and one queue worker, both `RUNNING`.

## Phase 6: Test before DNS moves

DNS still points at the old server, so make requests to the new server's IP
directly. Use port 80 for now, since there's no certificate yet:

```bash
curl -s -o /dev/null -w '%{http_code}\n' --resolve <domain>:80:<NEW_IP> http://<domain>/up      # 200
curl -s --resolve <domain>:80:<NEW_IP> http://<domain>/api/v1/home | head -c 300               # JSON with "moods"
curl -s --resolve <domain>:80:<NEW_IP> http://<domain>/ | grep -c '__nuxt'                     # ≥1
```

To prove Meilisearch works, find the collection search route with
`php artisan route:list --path=api`, then call it with a search term the
same way.

If Forge has already put an HTTP→HTTPS redirect on the site, these return
301. In that case go to Phase 7 first and test with `--resolve
<domain>:443:<NEW_IP> https://...`.

For a real browser test, the owner can add `<NEW_IP> <domain>` to their
local `/etc/hosts`, check the site, and **remove the line afterwards**.

🚧 Gate: all checks pass against the new server.

## Phase 7: SSL

Pick whichever of these the owner's setup allows. Recommend them in this
order:

1. **Cloudflare-proxied DNS.** The origin certificate setup is the same on
   both servers. Install the same origin cert or key on the new site.
2. **Clone the certificate** from the old site, if the new site's SSL tab
   offers it. Otherwise have the owner install it as an existing certificate
   by copying the cert and key from the old site's SSL tab. The old
   certificate stays valid on the new server until it expires. Reissue with
   Let's Encrypt after cutover.
3. **Let's Encrypt after cutover.** Issuing needs DNS to point at the new
   server, so HTTPS fails for a minute or two after the switch. That's
   acceptable for this app if the owner is fine with it.

⚠️ After any SSL change, check the nginx config again. Forge regenerates
parts of it when certificates or domains change, and can put the default
`location /` catch-all back.

## Phase 8: Cutover

1. Check nothing has changed since Phase 4. The weekly sync hasn't run and
   Settings haven't been edited. If something has, repeat Phase 4 and
   re-import the search index.
2. Change the DNS A record for `<domain>` to `<NEW_IP>`.
3. Watch `dig +short <domain>` until it returns the new IP.
4. If SSL option 3 was chosen, issue the certificate now and recheck nginx.
5. Run the [smoke test](#smoke-test) against the real domain over HTTPS.
6. Turn on **Push to deploy** on the new site.
7. On the **old** site, turn off the Scheduler and queue worker, so it no
   longer syncs the weekly data into a database nobody reads. Leave the site
   and its data in place.

🚧 Gate: the smoke test passes on the live domain.

## Phase 9: Settle and clean up

1. Run the background jobs once by hand to prove the queue, Discogs and
   Hugging Face all work on the new server: `php artisan discogs:sync`, then
   `php artisan personality:generate`. Watch the queue worker's log.
2. Reboot the new server. Confirm the Nuxt daemon, queue worker and
   Meilisearch all come back without help.
3. Put the DNS TTL back to its old value.
4. Keep the old server as a fallback for **one to two weeks**. After that,
   and only when **every** site on it has moved, keep a final DB dump
   somewhere safe and delete the server in Forge. Deleting it can't be
   undone, so get the owner's explicit confirmation first.
5. Optional follow-up PR: tighten `composer.json` to `"php": "~8.5.0"` and
   add `"engines": { "node": ">=24" }` to `frontend/package.json`.

## Rollback

Until Phase 9 step 4, rolling back means **pointing DNS back at the old
server's IP**. The old server still has its code, data, certificate and
config, unchanged. Two catches:

- Data written on the new server after cutover (a weekly sync, Settings
  edits) isn't on the old server. If a sync ran, run `discogs:sync` on the
  old server after rolling back.
- If the old server's Scheduler and queue worker were turned off in Phase 8
  step 7, turn them back on.

Problems before cutover, in Phases 3–7, don't affect production at all.
Fix them on the new server.

---

## Reference

### Deploy script

```bash
$CREATE_RELEASE()

cd "$FORGE_RELEASE_DIRECTORY"

$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader

$FORGE_PHP artisan migrate --force
$FORGE_PHP artisan config:cache
$FORGE_PHP artisan route:cache

cd frontend
npm ci
set -a; source ../.env; set +a
npm run build
cd ..

$ACTIVATE_RELEASE()

$RESTART_QUEUES()

sudo supervisorctl restart daemon-<id>:*
```

- Keep the explicit `cd "$FORGE_RELEASE_DIRECTORY"`. Forge's docs say it's
  inserted automatically, but on the old server testing showed it wasn't.
- `source ../.env` exports `VITE_GOOGLE_TAG_ID` into the build.
  `nuxt.config.ts` reads it from `process.env` at build time.
- The daemon restart **must come after `$ACTIVATE_RELEASE()`**. Before the
  swap, `current` still points at the previous release.
- No FPM reload and no `queue:restart` are needed. Zero-downtime deploys land
  in a fresh directory, and `$RESTART_QUEUES()` restarts the workers.
- `<id>` is the numeric id Forge shows on the site's Daemons tab.

### Nuxt daemon

Forge → site → Daemons:

- **Directory:** `/home/forge/<domain>/current/frontend`. It must go
  through `current`, not the site root.
- **User:** `forge`
- **Command:**
  ```
  env PORT=3004 NUXT_PUBLIC_API_BASE=https://<domain>/api/v1 bash deploy/start-nuxt.sh
  ```

The leading `env` is required. Supervisor runs the command without a shell,
so a bare `VAR=value cmd` fails. `NUXT_PUBLIC_API_BASE` goes on the Command
line because Forge daemons don't load the site's `.env`.
`start-nuxt.sh` exits with an error if it's missing.

### nginx config

Forge → site → Edit Nginx Configuration. Replace **only** the default
catch-all block:

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

with:

```nginx
location /api {
    try_files $uri $uri/ /index.php?$query_string;
}

location = /up {
    try_files $uri $uri/ /index.php?$query_string;
}

location /_nuxt/ {
    proxy_pass http://127.0.0.1:3004;
    proxy_set_header Host $host;
    add_header Cache-Control "public, max-age=31536000, immutable";
}

location / {
    proxy_pass http://127.0.0.1:3004;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection 'upgrade';
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
    proxy_cache_bypass $http_upgrade;
}
```

Leave everything else alone: the `location ~ \.php$` block, the SSL lines
and `server_name`.

### Smoke test

- `curl -s -o /dev/null -w '%{http_code}\n' https://<domain>/up` → `200`
- `curl -s https://<domain>/api/v1/home` → JSON containing `moods`
- `curl -s -o /dev/null -w '%{http_code}\n' https://<domain>/` → `200`, and
  the page source contains `<div id="__nuxt">`
- In a real browser: pages load, mood suggestions work, vibe search works,
  and collection search autocomplete works.
- If `VITE_GOOGLE_TAG_ID` is set: in DevTools → Network, look for a request
  to `googletagmanager.com/gtag/js`.
- `sudo supervisorctl status` → exactly one Nuxt daemon and one queue
  worker, both `RUNNING`.

### Troubleshooting

| Symptom | Cause | Fix |
|---|---|---|
| `can't find command 'NUXT_PUBLIC_API_BASE=...'` | Supervisor runs the command without a shell | Put `env` at the start of the daemon Command |
| `BACKOFF: Exited too quickly` | `frontend/.output/` doesn't exist yet | Run a full deploy first, then start the daemon |
| `ERROR (spawn error)` | Daemon never started successfully | Click Start on the daemon in Forge, then confirm `RUNNING` |
| `deploy/start-nuxt.sh: No such file or directory` | Daemon Directory is wrong | Set it to `/home/forge/<domain>/current/frontend` |
| nginx `502 Bad Gateway` | Nothing listening on the port, or nginx not reloaded | `sudo supervisorctl status`, then `sudo nginx -t && sudo service nginx reload` |
| `EADDRINUSE ... :3004` | Duplicate daemon, or another process on the port | Delete duplicate daemons. Run `sudo ss -tlnp \| grep :3004` and pick another port if needed |
| Site shows another app's content | Port collision | Same as above: pick a free port, then update the daemon and nginx |
| Deploy ends with `Killed` | Out of memory during `npm ci` or the build | Add swap, or set `NODE_OPTIONS=--max-old-space-size=...` |
| `composer install` platform error about PHP | `composer.json` still says `~8.3.0` | Phase 0 step 1 wasn't merged or deployed |
| Search returns nothing | Index not rebuilt, or wrong `MEILISEARCH_KEY` | Re-run the `scout:` commands from Phase 5. Check `curl 127.0.0.1:7700/health` |
