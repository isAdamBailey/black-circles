# frontend

Nuxt 4 SPA — the only frontend this app has. See the root [`CLAUDE.md`](../CLAUDE.md)
for the architecture and [`../DEPLOY.md`](../DEPLOY.md) for how this is run in
production (a Forge Daemon running the built Node server behind nginx).

## Setup

```bash
npm install
```

`.env` (copy from `.env.example`) sets `NUXT_PUBLIC_API_BASE`, which defaults to
`http://localhost/api/v1` — the Laravel API served by Sail.

## Commands

```bash
npm run dev         # dev server on http://localhost:3000
npm run build       # production build (node-server output) — what actually ships
npm run generate    # static prerender — not used in production
npm run test        # Vitest
npm run typecheck   # vue-tsc via `nuxi typecheck`
```
