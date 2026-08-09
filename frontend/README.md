# frontend

Nuxt 4 SPA that will replace the Inertia frontend in `resources/js/`. See the root
[`CLAUDE.md`](../CLAUDE.md) and issue #5 for the migration plan — this app is not
served in production until Phase 5 (PR 9).

## Setup

```bash
npm install
```

`.env` (copy from `.env.example`) sets `NUXT_PUBLIC_API_BASE`, which defaults to
`http://localhost/api/v1` — the Laravel API served by Sail.

## Commands

```bash
npm run dev         # dev server on http://localhost:3000
npm run build       # production build (node-server output)
npm run generate    # static SPA build — what actually ships (see Phase 5)
npm run test        # Vitest
npm run typecheck   # vue-tsc via `nuxi typecheck`
```
