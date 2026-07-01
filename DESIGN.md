---
name: Black Circles
description: A personal vinyl collection browser with AI mood matching.
colors:
  void: "#030712"
  cabinet: "#111827"
  shelf: "#1f2937"
  groove: "#374151"
  jacket: "#4b5563"
  dust: "#6b7280"
  label: "#9ca3af"
  sleeve: "#e5e7eb"
  pressing: "#ffffff"
  oxblood: "#7c1f25"
  signal-success-bg: "#14532d"
  signal-success-text: "#86efac"
  signal-error-bg: "#450a0a"
  signal-error-text: "#fca5a5"
typography:
  display:
    fontFamily: "Figtree, ui-sans-serif, system-ui, sans-serif"
    fontSize: "clamp(2.25rem, 6vw, 3rem)"
    fontWeight: 700
    lineHeight: 1.1
    letterSpacing: "-0.02em"
  headline:
    fontFamily: "Figtree, ui-sans-serif, system-ui, sans-serif"
    fontSize: "1.25rem"
    fontWeight: 600
    lineHeight: 1.3
  title:
    fontFamily: "Figtree, ui-sans-serif, system-ui, sans-serif"
    fontSize: "1.125rem"
    fontWeight: 600
    lineHeight: 1.4
  body:
    fontFamily: "Figtree, ui-sans-serif, system-ui, sans-serif"
    fontSize: "1rem"
    fontWeight: 400
    lineHeight: 1.6
  label:
    fontFamily: "Figtree, ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.75rem"
    fontWeight: 600
    lineHeight: 1
    letterSpacing: "0.08em"
rounded:
  sm: "6px"
  md: "8px"
  lg: "12px"
  xl: "16px"
spacing:
  xs: "8px"
  sm: "12px"
  md: "16px"
  lg: "24px"
  xl: "32px"
  2xl: "48px"
components:
  button-primary:
    backgroundColor: "{colors.pressing}"
    textColor: "{colors.void}"
    rounded: "{rounded.md}"
    padding: "10px 24px"
  button-primary-hover:
    backgroundColor: "{colors.sleeve}"
    textColor: "{colors.void}"
  button-secondary:
    backgroundColor: "{colors.shelf}"
    textColor: "{colors.pressing}"
    rounded: "{rounded.md}"
    padding: "10px 24px"
  button-secondary-hover:
    backgroundColor: "{colors.groove}"
  input-text:
    backgroundColor: "{colors.cabinet}"
    textColor: "{colors.pressing}"
    rounded: "{rounded.md}"
    padding: "12px 16px"
  card:
    backgroundColor: "{colors.cabinet}"
    textColor: "{colors.pressing}"
    rounded: "{rounded.xl}"
    padding: "24px"
---

# Design System: Black Circles

## 1. Overview

**Creative North Star: "The Specialist's Cabinet"**

Black Circles is a collector's display case — dark, purposeful, and slightly austere. Every surface is calibrated to recede so that album art and metadata can speak. The UI provides the frame; the records are the light source. Nothing decorates for its own sake.

The depth system is purely tonal: void (`#030712`) as the body, cabinet (`#111827`) for surfaces, shelf (`#1f2937`) for raised interactive elements. No shadows. No blur. The hierarchy reads through darkness, not through chrome. A single oxblood accent (`#7c1f25`) breaks the neutrals only where interaction demands it — active states, focus rings, key labels. Its rarity is the point.

This system explicitly rejects the energy of streaming platforms — rounded cards, algorithmic suggestion decks, "For You" vibes. It rejects the SaaS dashboard: no sidebar navigation, no blue primary buttons, no metric cards. The AI inside the app is a quiet concierge. It should not be visible in the design at all.

**Key Characteristics:**
- Dark-field, tonal elevation — no shadows
- One restrained accent color (oxblood), used sparingly
- Density as an aesthetic choice, not a problem to solve
- Album art is always the most prominent element on any given screen
- Type is functional — one weight family, hierarchy through size and color only

## 2. Colors

A near-monochrome dark palette; the oxblood accent earns its place by appearing almost nowhere.

### Primary
- **Oxblood** (`#7c1f25`): The sole accent. Used for active navigation states, focus rings, interactive highlights, and the occasional key label. Never on large surfaces. Never as a background. Its scarcity is the mechanism — when it appears, it signals.

### Neutral
- **Void** (`#030712`): Body background. The darkest surface. Everything sits on top of this.
- **Cabinet** (`#111827`): Primary surface — cards, nav bar, modals, insight panels.
- **Shelf** (`#1f2937`): Raised interactive surfaces — secondary buttons, filter chips, hover states on cabinet-level elements.
- **Groove** (`#374151`): Borders on hover and active focus indicators (non-accent).
- **Jacket** (`#4b5563`): Dividers, quiet separators, non-interactive borders.
- **Dust** (`#6b7280`): Tertiary text — timestamps, metadata labels, disabled states. Must not be used for body text (fails 4.5:1 against Void).
- **Label** (`#9ca3af`): Secondary text — supporting copy, nav links at rest. Minimum for any prose context.
- **Sleeve** (`#e5e7eb`): Hover tint for the white primary button. Not used independently.
- **Pressing** (`#ffffff`): Primary text, icon fills, the primary button surface. The lightest element in the stack.

### Signal
- **Success** (bg `#14532d`, text `#86efac`): Flash messages — kept to a small badge/bar; never used on content surfaces.
- **Error** (bg `#450a0a`, text `#fca5a5`): Flash messages.

### Named Rules

**The Rarity Rule.** Oxblood appears on ≤5% of any screen. One active nav link, one focus ring, one key label at most. If it appears twice on the same view, one instance is wrong.

**The Contrast Mandate.** Body and supporting prose must use Label (`#9ca3af`) or brighter. Dust (`#6b7280`) is permitted only for non-content metadata — never running text.

## 3. Typography

**Display / Body Font:** Figtree (400, 500, 600) — loaded from Bunny Fonts. A geometric-humanist sans-serif: clean enough for catalogue density, warm enough to not feel clinical.

**Character:** Single-family system. Hierarchy reads through weight (400 → 600 → 700) and color (Pressing → Label → Dust). No display serif pairing; the collection imagery provides all the visual richness the type doesn't need to add.

### Hierarchy
- **Display** (700, clamp 2.25rem → 3rem, lh 1.1, ls -0.02em): Page titles only. Black Circles wordmark in the header is excluded (it's identity, not content).
- **Headline** (600, 1.25rem / 20px, lh 1.3): Section headings, card group titles, major feature labels.
- **Title** (600, 1.125rem / 18px, lh 1.4): Card headings, modal titles, in-page sub-sections.
- **Body** (400, 1rem / 16px, lh 1.6): All prose content, release descriptions, personality insight text. Max line length 65–75ch.
- **Label** (600, 0.75rem / 12px, ls 0.08em, uppercase): Metadata labels, status pills, AI-generated markers. Sparing use only — not on every element.

### Named Rules

**The No-Pairing Rule.** Figtree only, at all times. Introducing a second family (even a serif for display) breaks the catalogue density the system is calibrated for.

**The Uppercase Ceiling.** Uppercase + wide tracking appears only on Label-tier elements (metadata stamps, status markers). Never on buttons, body text, or headings above 12px.

## 4. Elevation

This system is flat by design. Depth is expressed through tonal layering: void → cabinet → shelf → groove. No `box-shadow` at any tier. No blur or backdrop-filter decoratively. The collector's cabinet has no glass doors.

Focus rings use the Groove border color (`#374151`) by default; interactive focus on accent-bearing elements uses Oxblood. Ring style: `outline: 2px solid; outline-offset: 2px`. No glow.

### Named Rules

**The No-Shadow Rule.** `box-shadow` is prohibited on all product surfaces — cards, modals, nav, buttons. Elevation is expressed by background-color step, never by shadow. If you're reaching for a shadow, reach for a darker background instead.

## 5. Components

### Buttons
- **Shape:** Gently curved edges (6px radius, `rounded-md`)
- **Primary:** White surface (`#ffffff`) with void text (`#030712`), padding 10px 24px. Hover shifts to Sleeve (`#e5e7eb`). Used for single primary actions per view (e.g. "Random release").
- **Secondary:** Shelf surface (`#1f2937`) with Pressing text, Jacket border (`#4b5563`). Hover shifts to Groove (`#374151`). Used for navigation CTAs ("Browse collection").
- **Focus:** `outline: 2px solid #374151; outline-offset: 2px`
- **Disabled:** 50% opacity. No pointer events. No style change beyond opacity.
- **Ghost / Destructive:** Not currently in the system. Add only if needed.

### Cards / Containers
- **Corner Style:** Moderately curved (12–16px radius). Mood tiles use `rounded-2xl` (16px); insight panels use `rounded-xl` (12px).
- **Background:** Cabinet (`#111827`) for all cards and panels.
- **Shadow Strategy:** None. See Elevation.
- **Border:** `1px solid #1f2937` (Shelf) at rest; `2px solid #374151` (Groove) on hover for interactive cards (mood tiles, album cards). Border width changes on hover — not color — to preserve the dark field.
- **Internal Padding:** 24px standard (`p-6`). Dense catalogue views may use 16px.

### Inputs / Fields
- **Style:** Cabinet background (`#111827`), Jacket border (`#4b5563`), 8px radius, Pressing text.
- **Placeholder:** Dust color (`#6b7280`). This hits borderline contrast — watch for 3:1 minimum on placeholder.
- **Focus:** Border shifts to Label (`#9ca3af`), `ring-1 ring-gray-400`.
- **Disabled:** 50% opacity.
- **Search typeahead:** Absolute-positioned dropdown on top of fixed-position stack to avoid clipping by parent overflow containers. Z-index: dropdown tier.

### Navigation
- Cabinet background (`#111827`), Jacket border-bottom. Sticky (`top: 0`, `z-index: 50`).
- Nav links at rest: Label color (`#9ca3af`), 14px/500. Active route: Pressing white. Hover: Pressing white, transition 150ms.
- Brand wordmark: Pressing white, 20px/700, tight tracking. VinylRecordLogo SVG at 32×32px.
- Mobile: simplified link row, no hamburger — collection link inline at top right.

### Mood Tiles (Signature Component)
Emoji-led square cards for picking a listening mood. Cabinet background, double-width Shelf border (`border-2 border-gray-800`), 16px radius. On hover: border shifts to Groove, background nudges to `gray-800/80`. Emoji scales 1.1× on hover via `transform: scale(1.1)`. Label text in Pressing white, Title weight.

### Album Art Grid
Album art is always the visual anchor. Art is square and fills the card. Text metadata (title, artist, year) sits below in a tight stack: Title weight for title, Body for artist, Label-tier for year/price. Card hover should lift the art, not the card.

### Flash / Alert
Small horizontal bar at top of content area. Success: bg `#14532d`, text `#86efac`, border `#15803d`. Error: bg `#450a0a`, text `#fca5a5`, border `#991b1b`. Rounded-lg, `px-4 py-3`, Body size. Shown only on navigation, dismissed automatically.

## 6. Do's and Don'ts

### Do:
- **Do** let album art be the most visually prominent element on any screen. Crop it square; give it space.
- **Do** use tonal elevation (Cabinet → Shelf → Groove) to create depth. Never use `box-shadow`.
- **Do** treat the Oxblood accent as a signal, not a style. One active state per view at most.
- **Do** use Label (`#9ca3af`) as the minimum text color for any prose or supporting copy — Dust is for non-content metadata only.
- **Do** keep the AI invisible. Vibe search and mood input are tools. No robot icons, no "Powered by AI" labels, no loading theaters that announce the mechanism.
- **Do** embrace density. The collection is hundreds of records. Let the catalogue be dense — that's what collectors love about Discogs.

### Don't:
- **Don't** use Spotify-style visual language: rounded pill cards, green-tinted dark mode, algorithm-surfaced "For You" sections. This is a specialist tool, not a streaming service.
- **Don't** apply a SaaS dashboard aesthetic: no sidebar navigation, no blue primary buttons, no metric cards, no `border-left` accent stripes on callouts.
- **Don't** put "AI" front and center in any UI copy or iconography. The intelligence is a concierge. It does not have a face.
- **Don't** use `box-shadow` on any product surface. If you need depth, use a darker background tier.
- **Don't** use uppercase tracking on elements above Label tier. The `tracking-widest uppercase text-xs` pattern is reserved for metadata stamps only.
- **Don't** let the oxblood accent appear on backgrounds, large text, or more than one element per view. Its power comes from restraint.
- **Don't** use Dust (`#6b7280`) for body text or any text the user needs to read. It fails 4.5:1 contrast against Void.
- **Don't** introduce a second typeface. Figtree only.
