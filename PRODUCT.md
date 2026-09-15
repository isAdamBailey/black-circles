# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

Adam and a small circle of friends with shared taste. They use this in the evening, picking records to play — mood-first, not search-first. They know the collection already; the app helps them decide what to put on. No onboarding required; familiarity and efficiency matter more than discoverability for strangers.

The landing page (`/`) is also semi-public: occasionally shared with people who haven't used the app before, so it should hold its own as a standalone impression of the collection and the person behind it.

## Product Purpose

A personal vinyl collection browser powered by AI mood matching. You describe what you want to hear, the app finds it in the collection. The Discogs catalogue is the corpus; the AI (HuggingFace zero-shot + LLM) is a concierge, not the feature. Success looks like: the right record on the turntable in under 60 seconds.

## Positioning

Two things a generic music app couldn't truthfully claim:

1. **Real inventory, not a catalog of everything.** Mood/vibe matching runs only against records Adam actually owns — the corpus is his Discogs collection, not a licensed streaming library.
2. **A personality profile generated from that same real collection data**, not a generic taste quiz or algorithmic genre tag.

## Operating Context

- Single-owner personal tool — no accounts, no multi-tenant concerns, no login flow. "Adam's collection" is a fixed fact throughout the UI, not a variable.
- Weekly sync (Sunday midnight PST cron) pulls the Discogs collection and regenerates the personality insight; both jobs run on the database queue worker, not inline.
- Deployed via Laravel Forge — Laravel (PHP-FPM) + a Nuxt Node process behind nginx, single origin. See `DEPLOY.md`.
- The landing page is occasionally the very first thing a stranger sees (shared link), so it must read as complete and intentional standalone, not just as an authenticated app shell.

## Capabilities and Constraints

- Mood-based browsing (curated mood tiles → matched releases), plain-English vibe search (zero-shot classification via `MoritzLaurer/deberta-v3-base-zeroshot-v2.0`), and an AI-generated personality insight (`Qwen/Qwen2.5-1.5B-Instruct`) — all synchronous except the weekly sync/regeneration jobs.
- Collection browsing: full grid with search/filter/sort, release detail, and random-release discovery (now a first-class entry point on the home page, the collection page, and via a bare `/random` URL suitable for direct/QR traffic).
- Full-text search backed by Meilisearch (Laravel Scout), populated during `discogs:sync`.
- `HUGGINGFACE_API_TOKEN` must be set for the AI features to function; without it, mood/vibe/personality features are unavailable (collection browsing still works).
- SPA mode (`ssr: false`) — every page fetches client-side; no server-rendered pass.

## Brand Commitments

- Name: **Black Circles**. Wordmark + `VinylRecordLogo` mark in the nav.
- Voice/personality: calm, curatorial, considered — like a specialist record shop that trusts its stock. Nothing shouts, nothing overexplains. The collection is the product; the interface is the display case. Reference point: Discogs itself — dense catalogue energy, data-rich, collector obsession made tactile. Density is intentional, not a problem to solve.
- Anti-references (identity constraints, not just style notes):
  - **Spotify / streaming dark UI** — rounded cards, algorithmic "For You" energy, mass-market feel. This is for obsessives, not casual listeners.
  - **SaaS dashboard aesthetic** — sidebar nav, blue primaries, metric cards. This is a music tool; the only metric that matters is "what should I play."
  - **AI-first interfaces** — prompt boxes as the hero, robot iconography, "powered by AI" everywhere. The AI is a quiet concierge, never the face of the product.

## Evidence on Hand

None. This is a personal tool for a small circle of friends — there are no testimonials, usage numbers, press, or case studies, and none should be invented or implied by future work.

## Product Principles

1. **The collection is the corpus, not a demo.** Every feature — mood matching, vibe search, personality insight, random release — operates on Adam's real, owned inventory. Nothing is a mocked or generic example.
2. **The AI is a concierge, not the feature.** Mood input, vibe search, and the personality insight stay invisible as mechanisms — no AI branding, no robot iconography, no loading theatrics that draw attention to how the answer was produced.
3. **One person, one voice, everywhere.** No accounts, no multi-tenant complexity, no context switch between "marketing mode" and "tool mode." The landing page and the app share the same DNA because they're serving the same person.
4. **Speed to decision.** Success is a record on the turntable in under 60 seconds. Every new surface (random release, mood tiles, search) is another fast path to that outcome, not a competing feature.
5. **The landing page must hold up unaccompanied.** It's semi-public and is sometimes a stranger's only impression of the collection and the person behind it — it can't rely on prior context to make sense.

## Accessibility & Inclusion

WCAG AA minimum. Dark theme is committed — body text must hit ≥4.5:1 against `gray-950`. All interactive elements keyboard-navigable. Reduced-motion alternative for any animation added.
