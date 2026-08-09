# Deploying to Forge

As of Phase 5 (issue #5), Laravel is a JSON API only (`routes/api.php`) — no pages
of its own. [`frontend/`](frontend/) (Nuxt 4, SPA mode) is the only UI.

```
Browser
  │
  ▼
nginx (single Forge site, existing domain/SSL unchanged)
  ├─ /api/*  →  PHP-FPM  →  Laravel (public/index.php)
  └─ everything else  →  proxy_pass 127.0.0.1:<port>  →  Node (Nuxt .output/server/index.mjs)
```

**Don't assume `:3000` is free.** A prior migration on a shared Forge server
([familytribute-base](https://github.com/isAdamBailey/familytribute-base))
found `3000` and `3001` already occupied by unrelated processes (a PM2
process and another Forge-managed daemon) — Forge/Supervisor won't warn you
about a collision with something outside its own management, and the
symptom is confusing (nginx reaches *something*, just not this app — see the
troubleshooting table). **Check the server before picking a port** (step 2
below), rather than defaulting to `3000`.

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

## 0. Confirm auto-deploy is on

Forge's **Push to deploy** should already be enabled on this site (Deployments
tab) — nothing about triggering deploys changes here.

## 1. Site settings (Forge → site → General)

Unchanged: Web Directory `/public`, PHP 8.3. Laravel still boots from
`public/index.php`; what changes is which requests nginx sends there.

## 2. Add a Daemon for Nuxt (Forge → site → Daemons)

**First, pick a free port.** SSH into the server and check what's already
bound before assuming `3000` is available:

```bash
sudo ss -tlnp | grep -E ':(300[0-9]|301[0-9]|400[0-9])\b'
```

Pick the lowest port *not* listed in that output (start scanning from `3000`
upward — `3002` is a reasonable first guess given the collisions found
elsewhere, but verify, don't assume). Use that port everywhere `<port>`
appears below, including in the nginx block in step 4.

- **Directory:** `/home/forge/<domain>/frontend` — must be the `frontend`
  subfolder, not the site root, since the Command below is a relative path.
- **User:** `forge`
- **Command:**
  ```
  env PORT=<port> NUXT_PUBLIC_API_BASE=https://<domain>/api/v1 bash deploy/start-nuxt.sh
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
removed Inertia frontend):

```bash
cd /home/forge/<domain>

git pull origin $FORGE_SITE_BRANCH

$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader

$FORGE_PHP artisan migrate --force
$FORGE_PHP artisan config:cache
$FORGE_PHP artisan route:cache
$FORGE_PHP artisan queue:restart

( flock -w 10 9 || exit 1; echo 'Restarting FPM...'; sudo -S service $FORGE_PHP_FPM reload ) 9>/tmp/fpmlock

cd frontend
npm ci
npm run build

sudo supervisorctl restart daemon-<id>:*
```

Forge names each daemon `daemon-<id>` (a numeric id it assigns once the
daemon exists — get it from that site's Daemons tab and substitute it above).
`view:cache` is dropped — there are no Blade views left to cache.

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
    proxy_pass http://127.0.0.1:<port>;
    proxy_set_header Host $host;
    add_header Cache-Control "public, max-age=31536000, immutable";
}

location / {
    proxy_pass http://127.0.0.1:<port>;
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
```

`NUXT_PUBLIC_API_BASE` is set on the Daemon's Command line (step 2), not
here — Forge's Environment tab isn't confirmed to reach Daemon processes.
`FRONTEND_URL` feeds `config/cors.php`; single-origin production (nginx
proxies both `/api` and `/` from the same domain) means CORS isn't actually
exercised browser-to-browser here, but it's still the correct value in case
that changes.

Also enable Forge's **Scheduler** (weekly `discogs:sync` +
`personality:generate`, see `routes/console.php`) and a **queue:work** daemon
(separate from the Nuxt daemon above).

## 6. Checklist

- [ ] No leftover/duplicate Nuxt daemon from earlier troubleshooting
      (`sudo supervisorctl status` — exactly one).
- [ ] Verified `<port>` is actually free on the server before using it
      (`sudo ss -tlnp | grep :<port>`), not just assumed.
- [ ] Daemon created (step 2), `PORT=<port>` and `NUXT_PUBLIC_API_BASE` on the
      Command line, Directory = `frontend/`.
- [ ] Daemon manually started once and confirmed `RUNNING`.
- [ ] Deploy Script updated (step 3) with the correct
      `supervisorctl restart daemon-<id>` program name.
- [ ] Nginx edited (step 4) — `/api` and `/up` to PHP-FPM, `/` (and
      `/_nuxt/`) proxied to `127.0.0.1:<port>` (same port as the daemon).
- [ ] Env vars set (step 5).
- [ ] `queue:work` daemon and Scheduler enabled.
- [ ] Deploy, then smoke-test:
  - `curl -s -o /dev/null -w '%{http_code}\n' https://<domain>/up` → `200`
  - `curl -s https://<domain>/api/v1/home` → JSON with `moods`
  - `curl -s -o /dev/null -w '%{http_code}\n' https://<domain>/` → `200`,
    served by Nuxt (view source: `<div id="__nuxt">`)
  - Collection search autocomplete works in the real UI
- [ ] Confirm the Nuxt daemon survives a server reboot (Supervisor should
      restart it automatically; verify via Forge's Daemons tab after a
      reboot).

## Rollback

1. Forge → Deployments → Rollback to the previous deploy.
2. Revert nginx to the default catch-all (`location / { try_files ... }`),
   or to whatever the pre-cutover Inertia config was.
3. Stop the Nuxt daemon — the rolled-back deploy serves pages via Inertia/PHP
   again, so nothing app-specific should be listening on `<port>`.

## Troubleshooting reference

| Symptom | Cause | Fix |
|---|---|---|
| `FATAL can't find command 'NUXT_PUBLIC_API_BASE=...'` | Supervisor execs the command directly, no shell | Prefix the whole command with `env` (step 2) |
| `BACKOFF: Exited too quickly` | `.output/` doesn't exist yet | Run a full deploy first (step 3 builds it), *then* start the daemon |
| `ERROR (spawn error)` | Daemon never successfully started, or is in a broken state | Manually click Start on the daemon in Forge's UI and confirm `RUNNING` |
| `bash: deploy/start-nuxt.sh: No such file or directory` | Daemon's Directory is the site root, not `.../frontend` | Fix Directory to `/home/forge/<domain>/frontend`; confirm with `ls` |
| `nginx 502 Bad Gateway` | Nothing listening on `<port>` (daemon down, or nginx not reloaded after a config edit) | `sudo supervisorctl status`; `sudo nginx -t && sudo service nginx reload` |
| `EADDRINUSE: address already in use 127.0.0.1:<port>` | Two processes trying to bind the same port — either a duplicate daemon for this site, or another site/process already using it | `sudo supervisorctl status` for duplicates of this site's daemon (delete the extra); `sudo ss -tlnp \| grep :<port>` to check for unrelated occupants before assuming a port is free |
| Site loads but shows unrelated/garbled content, or another app entirely | Port collision — nginx reached *something* on `<port>`, just not this app (another site or an unrelated process, e.g. PM2) | `sudo ss -tlnp \| grep :<port>` to see what's actually there; pick a different, verified-free port instead (redo steps 2 and 4 with the new port) |
| `npm ci` / deploy ends with `Killed` | OOM during the frontend build | Add swap on the Forge server, or lower `NODE_OPTIONS=--max-old-space-size` |
