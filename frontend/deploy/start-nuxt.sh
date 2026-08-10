#!/usr/bin/env bash
set -euo pipefail

# Entry point for the Forge Daemon that keeps the Nuxt frontend running in
# production (see ../../DEPLOY.md). Forge's Daemon feature runs a raw command
# under Supervisor — it doesn't load the site's .env or offer per-daemon env
# vars in the UI, so NUXT_PUBLIC_API_BASE must be set on the Daemon's Command
# line itself, not assumed here — see DEPLOY.md step 2.

cd "$(dirname "$0")/.."

: "${NUXT_PUBLIC_API_BASE:?Set NUXT_PUBLIC_API_BASE on the Forge Daemon Command line (see DEPLOY.md)}"

export PORT="${PORT:-3000}"
export HOST="${HOST:-127.0.0.1}"

exec node .output/server/index.mjs
