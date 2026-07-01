---
target: home page
total_score: 21
p0_count: 0
p1_count: 3
timestamp: 2026-07-01T00-59-31Z
slug: resources-js-pages-home-vue
---
## Design Health Score

| # | Heuristic | Score | Key Issue |
|---|-----------|-------|-----------|
| 1 | Visibility of System Status | 2 | Async vibe-search job has no feedback beyond the "Finding..." button label |
| 2 | Match System / Real World | 3 | Mood language and copy fit a personal-collection context well |
| 3 | User Control and Freedom | 2 | No way to cancel a submitted vibe search once it locks |
| 4 | Consistency and Standards | 2 | `PrimaryButton.vue` uses indigo + uppercase-tracked text, foreign to every other button on the page |
| 5 | Error Prevention | 2 | No guard/feedback path if the vibe-search POST fails |
| 6 | Recognition Rather Than Recall | 3 | Mood tiles (emoji + label) are self-explanatory |
| 7 | Flexibility and Efficiency | 2 | Mood picker always starts collapsed — an extra click every visit, even for the primary user |
| 8 | Aesthetic and Minimalist Design | 3 | Genuinely restrained, on-brand, good whitespace |
| 9 | Error Recovery | 1 | `submitVibe()` has no `onError` handler at all — silent failure |
| 10 | Help and Documentation | 1 | Empty-state help text is raw shell commands (`.env`, `sail artisan discogs:sync`), shown to anyone including strangers |
| **Total** | | **21/40** | **Acceptable — needs real improvement, not a redesign** |

## Anti-Patterns Verdict

**Does this look AI-generated? Partial, and in one specific spot.** The macro composition (centered stack, monochrome dark, no gradient text, no metric cards, no glassmorphism) reads as deliberately product-designed, not template slop. But `PrimaryButton.vue` — the vibe-search submit button — is an untouched Breeze scaffold component: `uppercase tracking-widest` text and an `indigo-500` focus ring. It's the one element that looks inherited rather than designed, and it sits on the page's actual interaction moment.

**Deterministic scan**: Clean — exit 0, zero findings from `detect.mjs` against `Home.vue`. No box-shadow, gradient, backdrop-blur, or side-stripe borders anywhere in the file. This confirms the AI-copy removal made in this session worked as intended: only the legitimate "AI generated" label on the personality-insight panel remains.

The detector did not separately flag it, but the LLM review's contrast concern is corroborated by source evidence: two prose strings sit below the design system's stated "Label tier is the minimum for prose" rule —
- Line 116: `text-gray-600` on "Uses Adam's collection — results may vary" (gray-600 is darker than even the system's own worst-case `dust` token)
- Line 123: `text-gray-500` on the empty-state instructions

Also worth flagging: the "Adam's music personality" eyebrow (line 157, `uppercase tracking-wider text-gray-500`) is the tiny-uppercase-eyebrow pattern the system itself bans — the adjacent "AI generated" eyebrow (line 160) is the one exempted instance, not this one.

**Visual overlays**: Not available this run — no browser/screenshot tooling was present in either assessment's environment, so this was a static-source review, not a live render.

## Overall Impression

The page is calm, restrained, and mostly resists the SaaS/streaming clichés it's explicitly trying to avoid — the AI-copy cleanup lands well. But there's a mismatch between the stated identity ("mood-first, not search-first") and the actual hierarchy: "Random release" gets the highest-contrast treatment while the mood picker — the real primary interaction — is gray, collapsed by default, and one extra click away. The biggest single opportunity is fixing that inversion, closely followed by giving the async vibe-search job actual feedback (loading + error states), since right now a failed or slow search just looks broken.

## What's Working

- **AI stays invisible where it should.** The vibe-search input and mood tiles carry zero AI framing — only the personality panel is labeled, and minimally so. This is exactly the brand rule in practice, not just on paper.
- **The accordion-gated mood section is real progressive disclosure**, not decoration — it keeps the first screen to two buttons while still surfacing all 8 moods on demand. Correct application of "density is a feature."
- **Copy voice is honest and low-hype**: "Uses Adam's collection — results may vary" avoids the "AI-powered results tailored just for you" register entirely.

## Priority Issues

**[P1] Mood picker is visually subordinate to a secondary action.** "Random release" is a solid-white, high-contrast button; "Pick a mood" is a gray accordion trigger, collapsed by default, requiring a click just to see the actual mood-first interaction. This inverts the app's own stated priority.
- **Why it matters**: for a "mood-first, not search-first" product, burying the core interaction behind the least-emphasized element on the page actively works against the product's purpose — especially for the semi-public stranger-facing use case.
- **Fix**: either promote "Pick a mood" to the higher-contrast button style, or default `moodSectionOpen` to `true`.
- **Suggested command**: `/impeccable layout`

**[P1] No error state when vibe search fails.** `submitVibe()` has no `onError` handler — a failed dispatch just silently resets the button to "Find it."
- **Why it matters**: users get no explanation when something breaks; they'll assume the app is broken or retry blindly.
- **Fix**: add `onError` with inline error text, reusing the existing flash-message pattern already in `AppLayout.vue`.
- **Suggested command**: `/impeccable harden`

**[P1] `PrimaryButton.vue` breaks the design system.** Uppercase-tracked text and an indigo focus ring appear nowhere else on the page or in the token list (single-accent oxblood system).
- **Why it matters**: it's the one component that visibly doesn't belong, right at the point of interaction.
- **Fix**: restyle to match the plain white/gray button language already used at lines 50 and 70, with a gray/oxblood focus ring instead of indigo.
- **Suggested command**: `/impeccable audit`, then `/impeccable polish`

**[P2] Prose text falls below the system's own contrast floor.** Line 116 uses `text-gray-600`, line 123 uses `text-gray-500` — both below the "Label tier minimum for prose" rule the design system itself sets.
- **Why it matters**: borderline/failing contrast on real body copy, not decorative text.
- **Fix**: bump both to `text-gray-400` (matches the hero subtext's already-correct treatment).
- **Suggested command**: `/impeccable audit`

**[P2] No loading state for the async vibe-search job beyond a button label.** Per the documented poll-based flow, this can take multiple seconds with zero visual feedback.
- **Why it matters**: risks re-clicks or users navigating away mid-search.
- **Fix**: add a subtle inline spinner or dimmed-form state with a short reassurance line.
- **Suggested command**: `/impeccable animate`

## Persona Red Flags

**Jordan (confused first-timer, relevant since the page is semi-public)**: If the collection isn't synced, they hit raw shell commands (`DISCOGS_USERNAME`, `sail artisan discogs:sync`) as body copy — this reads as an unfinished dev site, not a curated personal project, to a stranger.

**Casey (distracted mobile user)**: Two full-width stacked CTAs plus a collapsed mood section means real scroll distance before reaching the actual mood-first feature, adding a tap most visitors won't expect to need.

**Sam (accessibility/keyboard)**: The "Pick a mood" disclosure button has no `aria-expanded`/`aria-controls` wiring to its panel — a screen-reader user gets no signal it's an expand/collapse control. The `⚫` empty-state glyph has no `aria-hidden`, so it may get announced literally out of context.

## Minor Observations

- The mood-tile grid intentionally exceeds the "≤4 visible options" cognitive-load guideline (8 tiles) — reasonable here as one homogeneous choice type in a scannable 2×4 grid, not a real violation.
- No `cursor-not-allowed` on mood-tile links during `processing` — only `opacity-60`, a weaker inert signal.
- The personality-insight panel's "AI generated" label (small, honest, correctly scoped) is a good template for any future AI-touching UI elsewhere in the app.

## Questions to Consider

- If this is genuinely "mood-first," why does "Random release" — pure white fill, first thing you see — outrank the mood picker in visual weight?
- Should the raw `.env`/`sail artisan` empty state even render for anonymous visitors, or should unsynced state show a plain "nothing here yet" instead?
