# Deploying to Forge

As of Phase 5 (issue #5), Laravel is a JSON API only (`routes/api.php`) — no pages
of its own. [`frontend/`](frontend/) (Nuxt 4, SPA mode) is the only UI.

```
Browser
  │
  ▼
nginx (single Forge site, existing domain/SSL unchanged)
  ├─ /api/*  →  PHP-FPM  →  Laravel (public/index.php)
  └─ everything else  →  proxy_pass 127.0.0.1:3004  →  Node (Nuxt .output/server/index.mjs)
```

This app's Nuxt daemon uses port `3004` — confirmed free on this Forge
server. (A prior migration on a shared Forge server
([familytribute-base](https://github.com/isAdamBailey/familytribute-base))
found `3000`/`3001` already occupied by unrelated processes, with a
confusing symptom — nginx reached *something*, just not that app. If this
server's port situation ever changes, re-verify with
`sudo ss -tlnp | grep :3004` before assuming it's still free — see the
troubleshooting table.)

This is Forge's standard, officially-documented pattern for Node.js apps — a
Daemon running the Node process, with nginx `proxy_pass`ing to it — not a
workaround (see Laravel's own guide, [Deploying your Next.js App to
Forge](https://laravel.com/blog/deploying-your-nextjs-app-to-forge)). The app
has no auth (out of scope — see the tracking issue), so unlike a typical
Node+API split there's no Sanctum/session cookie wiring to worry about; the
only backend surface nginx needs to carve out is `/api`.

Nuxt runs in SPA mode (`ssr: false` — every page already does its own
client-side fetch with a loading/skeleton state), so the Node process here is
just serving the built SPA shell and static assets, not rendering pages
server-side. `nuxt build` (not `generate`) still produces a Node server
(`.output/server/index.mjs`) that does this, run as a Forge Daemon — see
`frontend/deploy/start-nuxt.sh`.

## 0. Order of operations — do this *before* merging the cutover PR

**Merging the Phase 5 PR (#14) and letting Forge auto-deploy it immediately
will break the site**, for two independent reasons: (1) the site's *current*
Deploy Script still runs `npm ci && npm run build` at the repo root for the
old Vite/Inertia frontend — that script no longer exists once #14 merges, so
the deploy fails partway through, potentially after `git pull` has already
replaced files on disk but before `config:cache`/FPM reload run; (2) even if
the deploy succeeded, nginx is still routing `/` to PHP-FPM, and Laravel has
no web routes left to answer with.

The fix is to **not couple the Forge changes to the PR merge**. `frontend/`
(the full Nuxt app — all pages, all `/api/v1/*` endpoints it depends on) is
already on `main` as of Phase 4 — Phase 5 only removes the *old* backend code
and changes how Forge deploys. So the whole Node/nginx side below can be
built and proven working while `main` still safely serves Inertia, and #14
merged only once that's confirmed:

1. Temporarily disable **Push to deploy** on the site (Deployments tab) so an
   unrelated push doesn't trigger a deploy mid-setup.
2. SSH in and manually build the frontend against current `main`:
   `cd /home/forge/<domain>/frontend && npm ci && npm run build`. Proves the
   build works before anything is wired up.
3. Step 2 below (create + start the Daemon on port `3004`, confirm
   `RUNNING`, `curl 127.0.0.1:3004` from the server). This only talks to
   the daemon directly — nginx and real users aren't affected yet.
4. Step 4 below (edit nginx, reload). **This is the actual user-facing
   cutover moment** — check the live domain immediately after. Do this
   before merging anything, while you can still trivially revert by
   re-editing nginx back to the default catch-all if something's wrong.
5. Step 3's Deploy Script update — safe now, since nginx/daemon are already
   correct and this doesn't require #14 to be merged.
6. Step 5 (env vars).
7. Re-enable Push to deploy, *then* merge #14. The triggered deploy runs the
   *new* script against the *new* code — low-risk, since nginx and the
   daemon were already proven working against the old code first.
8. Run the checklist at the bottom of this file.

Forge's **Push to deploy** should otherwise already be enabled on this site
(Deployments tab) — nothing about *triggering* deploys changes here, only
what a deploy does and when you let one happen.

**None of the above applies if this is a brand-new Forge site that has never
deployed successfully.** #14 has since merged, and there's no live traffic
serving Inertia to protect — the whole point of this staged ordering was to
avoid breaking an *existing* production site mid-migration. For a fresh site:
just get the Deploy Script (step 3) right first — there's nothing on disk to
manually build into yet, since nothing has been cloned there — then let a
deploy run, then set up the Daemon (step 2) once `frontend/.output/` actually
exists, then nginx (step 4).

## 1. Site settings (Forge → site → General)

Unchanged: Web Directory `/public`, PHP 8.3. Laravel still boots from
`public/index.php`; what changes is which requests nginx sends there.

## 2. Add a Daemon for Nuxt (Forge → site → Daemons)

Uses port `3004` (confirmed free on this server). If that ever needs to
change, double-check first with `sudo ss -tlnp | grep :3004` before reusing
or reassigning it.

- **Directory:** `/home/forge/<domain>/frontend` — must be the `frontend`
  subfolder, not the site root, since the Command below is a relative path.
- **User:** `forge`
- **Command:**
  ```
  env PORT=3004 NUXT_PUBLIC_API_BASE=https://<domain>/api/v1 bash deploy/start-nuxt.sh
  ```

The leading `env` matters: Supervisor execs the command directly with no
shell, so bare `VAR=value cmd` fails with `can't find command 'VAR=value...'`;
`env` is a real binary that sets the vars and execs the rest of the line
itself. `frontend/deploy/start-nuxt.sh` fails loudly if `NUXT_PUBLIC_API_BASE`
isn't set, rather than silently booting against the wrong API.

Forge's Daemon feature doesn't reliably load the site's `.env` or offer
per-daemon env vars in its UI, so the domain is set directly on the Command
line rather than assumed.

After the *first* deploy (step 3 builds `.output/`), start the daemon manually
once from the Daemons tab and confirm it shows `RUNNING` — Supervisor won't
auto-start a brand-new daemon.

## 3. Deploy Script (Forge → site → App / Deploy Script)

Replace the existing script (it still has `npm run build`/Vite steps for the
removed Inertia frontend).

**If this site has Zero Downtime Deployment enabled** (Forge's deploy log
says `=> Creating new release` / clones into `releases/<id>`), Forge already
clones the repo into the new release directory *and* runs the deploy script
from inside it — don't `cd` to the site root or `git pull` yourself; both
are not just unneeded but actively wrong (the site root path may not exist
yet as a symlink on a first deploy, and re-pulling on top of a fresh clone
is redundant):

```bash
$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader

$FORGE_PHP artisan migrate --force
$FORGE_PHP artisan config:cache
$FORGE_PHP artisan route:cache
$FORGE_PHP artisan queue:restart

( flock -w 10 9 || exit 1; echo 'Restarting FPM...'; sudo -S service $FORGE_PHP_FPM reload ) 9>/tmp/fpmlock

cd frontend
npm ci
set -a; source ../.env; set +a
npm run build

sudo supervisorctl restart daemon-<id>:*
```

**If Zero Downtime Deployment is *not* enabled** (standard deploy, no
`releases/` directories), prepend the two lines it would otherwise skip:

```bash
cd /home/forge/<domain>
git pull origin $FORGE_SITE_BRANCH

$FORGE_COMPOSER install ...
```

Forge names each daemon `daemon-<id>` (a numeric id it assigns once the
daemon exists — get it from that site's Daemons tab and substitute it above).
`view:cache` is dropped — there are no Blade views left to cache.

The `source ../.env` line matters specifically for `VITE_GOOGLE_TAG_ID` (see
step 5) — `nuxt.config.ts` reads it with a plain `process.env` lookup, not
Nuxt's `NUXT_PUBLIC_*` runtime-config convention, so it's only picked up if
it's an actual shell env var *at build time*. Bash scripts don't auto-source
`.env`, and Forge's Deploy Script doesn't either, so without this line the
variable Forge writes to `.env` from the Environment tab would silently never
reach `npm run build`. (This doesn't apply to `NUXT_PUBLIC_API_BASE` — that
one *is* the `NUXT_PUBLIC_*` convention, which Nitro re-reads dynamically at
server start regardless of what was baked in at build time, which is why it's
set on the Daemon's Command line instead.)

## 4. Nginx configuration (Forge → site → Files → Edit Nginx Configuration)

Forge's default site template has a catch-all
`location / { try_files $uri $uri/ /index.php?$query_string; }` that sends
every request into Laravel. Replace that one block; leave everything else
(the `location ~ \.php$` block, SSL directives, `server_name`) untouched.

Before (Forge default):
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

After:
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

`location /_nuxt/` is optional but recommended — Nuxt's own static assets
there are content-hashed and immutable, safe to cache aggressively.

**Caveat:** Forge regenerates parts of this file when you change the domain,
reissue an SSL certificate, or recreate the site — those actions can silently
revert this edit back to the default catch-all. Re-check and reapply the
blocks above after any such action.

## 5. Environment variables (Forge → site → Environment)

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://<domain>
FRONTEND_URL=https://<domain>
DB_*                                # MySQL credentials
QUEUE_CONNECTION=database
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://127.0.0.1:7700
DISCOGS_USERNAME=
DISCOGS_TOKEN=
HUGGINGFACE_API_TOKEN=
VITE_GOOGLE_TAG_ID=                  # optional — GA4 measurement ID, see below
```

`NUXT_PUBLIC_API_BASE` is set on the Daemon's Command line (step 2), not
here — Forge's Environment tab isn't confirmed to reach Daemon processes.
`FRONTEND_URL` feeds `config/cors.php`; single-origin production (nginx
proxies both `/api` and `/` from the same domain) means CORS isn't actually
exercised browser-to-browser here, but it's still the correct value in case
that changes.

`VITE_GOOGLE_TAG_ID` is the var name kept from the old Vite/vue-gtag setup —
`frontend/nuxt.config.ts` reads it directly to configure the `nuxt-gtag`
module. Unlike `NUXT_PUBLIC_API_BASE`, this one **is** read here (Environment
tab → `.env`), because the Deploy Script's `source ../.env` line (step 3)
exports it into the `npm run build` step. Leave it empty to run without
analytics — the module no-ops with no id set.

Also enable Forge's **Scheduler** (weekly `discogs:sync` +
`personality:generate`, see `routes/console.php`) and a **queue:work** daemon
(separate from the Nuxt daemon above).

## 6. Checklist

- [ ] No leftover/duplicate Nuxt daemon from earlier troubleshooting
      (`sudo supervisorctl status` — exactly one).
- [ ] Verified `3004` is actually free on the server before using it
      (`sudo ss -tlnp | grep :3004`), not just assumed.
- [ ] Daemon created (step 2), `PORT=3004` and `NUXT_PUBLIC_API_BASE` on the
      Command line, Directory = `frontend/`.
- [ ] Daemon manually started once and confirmed `RUNNING`.
- [ ] Deploy Script updated (step 3) with the correct
      `supervisorctl restart daemon-<id>` program name and the
      `source ../.env` line before `npm run build`.
- [ ] Nginx edited (step 4) — `/api` and `/up` to PHP-FPM, `/` (and
      `/_nuxt/`) proxied to `127.0.0.1:3004` (same port as the daemon).
- [ ] Env vars set (step 5).
- [ ] `queue:work` daemon and Scheduler enabled.
- [ ] Deploy, then smoke-test:
  - `curl -s -o /dev/null -w '%{http_code}\n' https://<domain>/up` → `200`
  - `curl -s https://<domain>/api/v1/home` → JSON with `moods`
  - `curl -s -o /dev/null -w '%{http_code}\n' https://<domain>/` → `200`,
    served by Nuxt (view source: `<div id="__nuxt">`)
  - Collection search autocomplete works in the real UI
  - If `VITE_GOOGLE_TAG_ID` is set: open `https://<domain>/` in a real
    browser (not `curl` — the gtag script injects client-side, post-hydration,
    same as the rest of this `ssr:false` app's `<head>` tags) and check
    DevTools → Network for a request to `googletagmanager.com/gtag/js`, or
    check GA4's Realtime report
- [ ] Confirm the Nuxt daemon survives a server reboot (Supervisor should
      restart it automatically; verify via Forge's Daemons tab after a
      reboot).

## Rollback

1. Forge → Deployments → Rollback to the previous deploy.
2. Revert nginx to the default catch-all (`location / { try_files ... }`),
   or to whatever the pre-cutover Inertia config was.
3. Stop the Nuxt daemon — the rolled-back deploy serves pages via Inertia/PHP
   again, so nothing app-specific should be listening on `3004`.

## Troubleshooting reference

| Symptom | Cause | Fix |
|---|---|---|
| `FATAL can't find command 'NUXT_PUBLIC_API_BASE=...'` | Supervisor execs the command directly, no shell | Prefix the whole command with `env` (step 2) |
| `BACKOFF: Exited too quickly` | `.output/` doesn't exist yet | Run a full deploy first (step 3 builds it), *then* start the daemon |
| `ERROR (spawn error)` | Daemon never successfully started, or is in a broken state | Manually click Start on the daemon in Forge's UI and confirm `RUNNING` |
| `bash: deploy/start-nuxt.sh: No such file or directory` | Daemon's Directory is the site root, not `.../frontend` | Fix Directory to `/home/forge/<domain>/frontend`; confirm with `ls` |
| `nginx 502 Bad Gateway` | Nothing listening on `3004` (daemon down, or nginx not reloaded after a config edit) | `sudo supervisorctl status`; `sudo nginx -t && sudo service nginx reload` |
| `EADDRINUSE: address already in use 127.0.0.1:3004` | Two processes trying to bind the same port — either a duplicate daemon for this site, or another site/process already using it | `sudo supervisorctl status` for duplicates of this site's daemon (delete the extra); `sudo ss -tlnp \| grep :3004` to check for unrelated occupants before assuming a port is free |
| Site loads but shows unrelated/garbled content, or another app entirely | Port collision — nginx reached *something* on `3004`, just not this app (another site or an unrelated process, e.g. PM2) | `sudo ss -tlnp \| grep :3004` to see what's actually there; pick a different, verified-free port instead (redo steps 2 and 4 with the new port) |
| `npm ci` / deploy ends with `Killed` | OOM during the frontend build | Add swap on the Forge server, or lower `NODE_OPTIONS=--max-old-space-size` |
