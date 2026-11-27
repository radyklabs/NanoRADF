# Design System Research — NanoRADF

Research date: 2026-11-27. Star counts verified via GitHub REST API on the same date (see `docs/REPORT.md` for the exact command).

## Purpose

NanoRADF renders HTML on the server. It has no bundler, no npm, no JS framework, and no shadow DOM (NFR-1: zero runtime dependencies). This document records the ten highest-starred design-system repositories on GitHub, what each one does, and which parts can be adopted without breaking that constraint.

This is a **research and decision document**. It does not claim that any item below is implemented. Implementation status is tracked in `docs/PLAN.md` Phase 8.

The document has two distinct parts:

- **Sections 1–8** — general research on the ten highest-starred design-system repositories, and the adoption priorities derived from them.
- **Section 8** — validation of the project owner's curated collection at `D:\Www\miniradf\app\system\Templates` against those repositories and against the sources the collection itself declares. Section 8 is the authoritative part for decisions about NanoRADF's design system.

---

## 1. The ten repositories

Ranked by star count at research time. "Archived" means the repository is read-only; its patterns remain usable.

**Scope note (added 2026-11-28):** this list was chosen by highest star count, not by provenance of the curated collection. Section 8 shows that only four of these ten actually validate sources cited by the collection. The list remains useful as general design-system research, but it is **not** the validation set for the collection.

| # | Repository | Stars | Archived | Core idea |
|---|------------|-------|----------|-----------|
| 1 | `twbs/bootstrap` | 175,141 | no | Mature CSS framework: reboot, `@layer` cascade, semantic tokens, grid, utilities, components |
| 2 | `jgthms/bulma` | 50,030 | no | Modern pure-CSS framework; HSL channel primitives → computed semantic variables |
| 3 | `google/material-design-lite` | 32,193 | no | Material Design in plain HTML/CSS/JS — no framework, no shadow DOM |
| 4 | `foundation/yeti` | 29,824 | no | CSS-first, native, zero-build styling and layout |
| 5 | `microsoft/fluentui` | 20,437 | no | Fluent design tokens + web components; token pipeline is the transferable part |
| 6 | `radix-ui/primitives` | 19,613 | no | Unstyled, accessible component primitives — behavior and ARIA only |
| 7 | `material-components/material-components-web` | 17,023 | yes | Per-component packages (`mdc-button`, `mdc-card`) + token resolver layer |
| 8 | `picocss/pico` | 16,805 | yes | Styles bare semantic HTML; classless variant; light/dark themes; `--pico-*` prefix |
| 9 | `primer/css` | 13,063 | no | GitHub's CSS: utility + component classes driven by `primer/primitives` tokens |
| 10 | `carbon-design-system/carbon` | 9,653 | no | DTCG JSON token source → generated CSS; explicit token metadata |

Also checked, below the top ten: `style-dictionary/style-dictionary` (4,898), `salesforce-ux/design-system` (3,700, archived), `adobe/spectrum-css` (1,295, archived), `patternfly/patternfly` (825), `vaadin/web-components` (586), `primer/primitives` (422).

---

## 2. What each repository contributes

### 2.1 `twbs/bootstrap` — 175,141 stars

**What it is.** The most-starred CSS framework. It defines a full design system: reset, tokens, layout, components, and utilities.

**Transferable patterns.**

1. **Reboot/normalize** — one stylesheet that neutralizes browser defaults so `<button>`, `<h1>`, `<input>` look identical across browsers. Without it, every component inherits browser-specific margins and fonts.
2. **`@layer` cascade** — Bootstrap declares `@layer colors, config, root, reboot, layout, content, forms, components, custom, helpers, utilities`. This makes override order explicit instead of relying on import order.
3. **Semantic tokens** — `--bg-*`, `--fg-*`, `--border-*` derived from palettes, not used directly by components.
4. **`[data-bs-theme="dark"]`** — explicit theme attribute alongside `prefers-color-scheme`. PHP can set this attribute server-side; no JavaScript needed.
5. **Focus-visible ring** — consistent keyboard focus treatment.
6. **Grid and container** — 12-column grid plus max-width containers.
7. **Utility classes** — spacing, display, flex, text, visibility.
8. **Form validation states** — `is-valid` / `is-invalid` with matching feedback text.

**Not transferable.** JavaScript plugins (modal, carousel, tooltip behavior) — NanoRADF uses vanilla JS only for progressive enhancement.

### 2.2 `jgthms/bulma` — 50,030 stars

**What it is.** Pure-CSS framework built on modern CSS features. No JavaScript required for layout or components.

**Transferable patterns.**

1. **HSL channel primitives** — colors stored as separate hue/saturation/lightness variables, then composed into semantic colors. A palette swap changes three numbers instead of dozens of hex values.
2. **Modifier classes** — `is-primary`, `is-small`, `is-rounded` compose with base classes instead of creating one class per variant.
3. **Responsive helpers** — mobile-first visibility and column classes.
4. **Classless-friendly structure** — many elements work without a class, which matches NanoRADF's semantic-HTML preference.

**Not transferable.** Sass mixin pipeline — NanoRADF ships plain CSS, so the computed values must be written as custom properties directly.

### 2.3 `google/material-design-lite` — 32,193 stars

**What it is.** Material Design implemented in plain HTML, CSS, and a small amount of JS. No framework, no shadow DOM.

**Transferable patterns.**

1. **Plain-HTML component markup** — components are ordinary tags with classes, directly renderable by PHP.
2. **Elevation scale** — shadow levels as a named token set (NanoRADF already has `--shadow-1..4`).
3. **Typography scale** — display/headline/title/body/caption roles mapped to size tokens.
4. **Ripple and state colors** — hover/focus/active states derived from one base color.

**Not transferable.** The JS ripple behavior; keep CSS-only state changes.

### 2.4 `foundation/yeti` — 29,824 stars

**What it is.** CSS-first, native styling framework. Emphasizes semantic HTML and zero build tooling.

**Transferable patterns.**

1. **Zero-build CSS** — no preprocessor required; matches NFR-1 directly.
2. **Semantic HTML defaults** — forms, tables, and typography styled through element selectors.
3. **Visibility classes** — show/hide by breakpoint without JS.
4. **Flexible grid** — percentage-based columns that degrade gracefully.

**Not transferable.** Its Sass source structure; only the compiled CSS ideas apply.

### 2.5 `microsoft/fluentui` — 20,437 stars

**What it is.** Microsoft's design system. Large token pipeline plus React/web-component implementations.

**Transferable patterns.**

1. **Token pipeline concept** — raw palette → alias tokens → component tokens. This is the same three-tier model NanoRADF adopted (D23).
2. **Token metadata** — each token declares intent (color, size, motion) and theme scope.
3. **Sizing tokens** — `--size-icon-sm/md/lg` style icon and control dimensions.

**Not transferable.** React components, web components, and the JS token build pipeline.

### 2.6 `radix-ui/primitives` — 19,613 stars

**What it is.** Unstyled, accessible component primitives. Provides behavior and ARIA contracts, not visual design.

**Transferable patterns.**

1. **ARIA contracts** — each component documents required roles and states (`role="alert"`, `aria-expanded`, `aria-controls`, `aria-current`).
2. **Keyboard interaction contract** — Tab, Enter, Escape, arrow keys for menus and tabs.
3. **Unstyled primitives** — separate behavior from appearance; NanoRADF can render the markup server-side and add CSS separately.
4. **Focus management** — where focus goes when a dialog opens or closes.

**Not transferable.** The React hooks and portals. Only the accessibility contract is useful.

### 2.7 `material-components/material-components-web` — 17,023 stars (archived)

**What it is.** Material Components for the Web. Per-component packages with a token resolver layer.

**Transferable patterns.**

1. **Per-component packages** — one folder per component (`button`, `card`, `textfield`) with its own CSS and token file. Maps cleanly to NanoRADF's `Components/` and `Elements/` folders.
2. **Token resolver layer** — component CSS consumes resolved tokens, never raw palette values.
3. **Component-level state tokens** — hover, focus, disabled, selected defined per component.

**Not transferable.** Sass mixins and JS foundation classes. Repository is archived; treat as reference only.

### 2.8 `picocss/pico` — 16,805 stars (archived)

**What it is.** The closest match to NanoRADF's philosophy: style semantic HTML directly, no classes required, no JS.

**Transferable patterns.**

1. **Bare-tag styling** — `<article>`, `<details>`, `<dialog>`, `<table>`, `<input>` look complete without classes. This is the single most useful idea for NanoRADF.
2. **Classless variant** — a build that relies entirely on element selectors.
3. **Module toggles** — opt in/out of styling groups (forms, tables, code) via CSS variables or class scopes.
4. **Light/dark themes** — semantic variable remapping, no JS.
5. **`--pico-*` prefix convention** — namespaced tokens avoid collisions with app CSS.

**Not transferable.** Its Sass source; NanoRADF writes plain CSS custom properties. Repository is archived but the pattern remains valid.

### 2.9 `primer/css` — 13,063 stars

**What it is.** GitHub's CSS implementation, driven by `primer/primitives` tokens.

**Transferable patterns.**

1. **Utility + component split** — utilities for one-off adjustments, components for reusable structures.
2. **Tokens from primitives** — functional tokens reference base tokens by name.
3. **Opacity and border-width tokens** — `--opacity-disabled`, `--border-width-thin` style values that NanoRADF currently hardcodes.
4. **Responsive utility naming** — breakpoint prefixes on utility classes.

**Not transferable.** GitHub-specific visual identity and the JS-free but npm-based build.

### 2.10 `carbon-design-system/carbon` — 9,653 stars

**What it is.** IBM's design system. Uses DTCG JSON as token source of truth and generates CSS.

**Transferable patterns.**

1. **DTCG token format** — structured JSON with `$value` and `$type`; useful if NanoRADF later wants a token source file.
2. **Token metadata** — `core`, `interactive`, `deprecated` classification prevents accidental use of unstable tokens.
3. **Explicit tier naming** — core → theme → component, matching D23.
4. **Accessibility as a first-class requirement** — contrast and focus are part of token definitions.

**Not transferable.** The Node build pipeline and web components.

---

## 3. Consolidated adoption list, ranked by priority

Priority reasoning: **P0** removes a correctness or accessibility gap; **P1** gives the largest visual/structural gain for the least code; **P2** is useful but can wait for a real use case; **P3** is reference-only or requires a build step that conflicts with NFR-1.

| Priority | Item | Source | Why it matters here | Fits NFR-1? |
|----------|------|--------|---------------------|-------------|
| **P0** | Reboot/normalize | Bootstrap, Yeti | Without it, every component inherits inconsistent browser defaults | yes — plain CSS |
| **P0** | Focus-visible ring on all interactive elements | Bootstrap, Radix, Carbon | Keyboard accessibility; currently only `.btn` and `.link` have it | yes |
| **P0** | `sr-only` + skip link | Radix, Bootstrap | Screen-reader and keyboard navigation baseline | yes |
| **P0** | ARIA contract per component | Radix | Prevents incorrect `role`/`aria-*` in SSR fragments | yes — markup only |
| **P1** | `@layer` cascade | Bootstrap | Already adopted in `System.css`; makes overrides predictable | yes |
| **P1** | Three-tier tokens | Spectrum, Primer, Carbon, Fluent | Already adopted as D23; prevents primitive leakage into components | yes |
| **P1** | `[data-theme]` server-set attribute | Bootstrap | PHP controls theme without JS | yes |
| **P1** | Bare-tag element styling | Pico, Yeti | Largest gain: semantic HTML looks complete with zero classes | yes |
| **P1** | Form primitives (Input, Select, Textarea, Checkbox, Radio, Toggle, Form field) | Bootstrap, Pico, MDC | Forms are the most common SSR surface | yes |
| **P1** | Table + List group | Pico, Bootstrap, MDC | Data display without JS | yes |
| **P1** | Utility classes (spacing, display, flex, text, visibility) | Bootstrap, Primer, Bulma | Removes one-off CSS; composes with components | yes |
| **P1** | Layout primitives (container, grid, cluster, sidebar) | Bootstrap, Yeti, Bulma | Page structure without a CSS framework | yes |
| **P2** | Toast, Dialog, Dropdown, Tooltip, Popover | Radix, MDC, Bootstrap | Need a JS enhancement contract; SSR markup can still be provided | markup yes, behavior progressive |
| **P2** | Tabs, Accordion, Breadcrumb, Pagination, Stepper | MDC, Bootstrap, Primer | Common navigation patterns; no JS needed for static state | yes |
| **P2** | Progress, Spinner, Skeleton, Empty state | Bootstrap, Carbon, Fluent | Feedback states; useful once real async flows exist | yes |
| **P2** | Avatar, Stat, Timeline, Quote, Figure, KBD, Code block | Primer, Carbon, MDL | Content components; low cost, incremental value | yes |
| **P2** | Opacity, z-index, icon-size tokens | Primer, Fluent | Removes hardcoded values in component CSS | yes |
| **P2** | Responsive utilities (`.md:flex`, `.lg:hidden`) | Bootstrap, Bulma, Primer | Breakpoint control without custom media queries | yes |
| **P3** | `prefers-contrast`, `forced-colors` | Carbon, Bootstrap | High-contrast mode support; needs real testing environment | yes, but low immediate use |
| **P3** | DTCG JSON token source | Carbon, Style Dictionary | Would introduce a build step; conflicts with zero-build simplicity | no — requires tooling |
| **P3** | HSL channel primitives | Bulma | Powerful but adds indirection; current hex palette is simpler | yes, but not worth the churn |
| **P3** | Web components / shadow DOM | Fluent, Carbon, Vaadin, MDL | Incompatible with SSR-first design | no |
| **P3** | React/JS behavior libraries | Radix, Fluent, MDC | Out of scope for vanilla JS progressive enhancement | no |

---

## 4. What is most applicable to NanoRADF

Ranked by fit, not by popularity:

1. **Pico CSS bare-tag styling** — NanoRADF already renders semantic HTML server-side. Styling bare tags directly gives the biggest visual improvement with the least markup and no JS.
2. **Bootstrap reboot + `@layer` + utilities** — provides the missing foundation layer. `@layer` is already in place; reboot and utilities are the natural next steps.
3. **Radix accessibility contracts** — NanoRADF's fragments are SSR, so the ARIA contract must be correct in the HTML itself. Radix documents this better than any CSS framework.
4. **Spectrum/Primer three-tier tokens** — already adopted as D23; continue by adding component tokens for every new component.
5. **PatternFly per-component folder structure** — one HTML fragment + one CSS block per component maps directly to `src/Views/Components/` and `src/Views/Tokens/components.css`.
6. **Bootstrap `[data-theme]`** — PHP sets `data-theme` on `<html>`; no client-side theme switcher needed.
7. **Carbon token metadata** — useful only if a token source file is introduced later; keep as reference.

---

## 5. Current implementation status (baseline at `195fdf5`, pre-Phase-8)

This section records only what exists at the pre-Phase-8 commit. It is **not** a claim of completion. The earlier version of this section described the uncommitted Phase 8 working tree and was incorrect as a baseline.

| Item | Status at `195fdf5` |
|------|--------|
| `@layer` cascade | **absent** — `Tokens/System.css` uses plain top-level `@import` only |
| Three-tier token naming | partial — `Tokens/color.css`, `space.css`, `typography.css`, `elevation.css`, `form.css`, `nav.css`, `motion.css`, `media.css` exist |
| `[data-theme]` dark/light remap | present in `Tokens/color.css`, `elevation.css`, `nav.css` |
| `System.<role>.<name>` view resolution | **absent** — `ViewsService` resolves app/module views only |
| SSR fragments | **absent** — `Components/Alerts.html`, `Elements/Badge.html`, `Patterns/Header.html` are the only view fragments (1 each) |
| Token contract test | **absent** — `tests/tokens_check.php` does not exist |
| Reboot/normalize | **absent** — no `Reboot.css` anywhere |
| `sr-only` / skip link | **absent** — no `Sr-Only.html` / `Skip-Link.html` |
| Utilities | **absent** — no `Utilities.css` |
| Bare-tag element styling | partial — `Tokens/form.css` and `Tokens/typography.css` only |
| Form primitives | **absent** |
| Table / List group | **absent** |
| Layout primitives | **absent** |
| Opacity / z-index / icon-size tokens | partial — `Tokens/color.css`, `media.css` |
| ARIA contract | **absent** in view fragments |
| Overlay / navigation / feedback / content components | **absent** |
| Responsive utilities | partial — `Tokens/media.css` breakpoints only |
| JS enhancements | **absent** — `Scripts/` contains one pre-existing file only |
| CSS outside design system | `Styles/Errors/core.css` (error-page stylesheet, pre-existing) |

**Folder rule (owner fact, 2026-11-28):** `Styles/` is for CSS **outside** the design system. All design-system CSS belongs in `Tokens/` only. `Styles/Errors/core.css` is an error-page stylesheet and stays in `Styles/`.

---

## 6. Decision record

- **Adopt:** P0 and P1 items. They are plain CSS/HTML, zero-dependency, and directly improve SSR output.
- **Defer:** P2 items until a real page needs them. No speculative components.
- **Reject:** P3 items that require npm, a build step, shadow DOM, or a JS framework — these violate NFR-1 or the SSR-first design.
- **Keep `@slot/@endslot` deferred** (D14) until a component genuinely needs content projection.

---

## 7. Verification method

Star counts in section 1 were verified with:

```powershell
$repos = @('twbs/bootstrap','jgthms/bulma','google/material-design-lite','foundation/yeti','microsoft/fluentui','radix-ui/primitives','material-components/material-components-web','picocss/pico','primer/css','carbon-design-system/carbon')
foreach ($r in $repos) {
  gh api "repos/$r" --jq '{full_name, stars: .stargazers_count, archived, pushed_at}'
}
```

Patterns in sections 2–4 were derived from each repository's published documentation and source layout. No code from these repositories was copied into NanoRADF; only design patterns are recorded here.

---

## 8. Validation of the curated collection (2026-11-28)

### 8.1 Source of truth

The curated collection lives at `D:\Www\miniradf\app\system\Templates`. It was assembled manually by the project owner from many design systems. It is the **reference set** for this validation — not the ten repositories in section 1.

Inventory (files counted from disk, extensions confirmed):

| Folder | Files | Extension | Notes |
|--------|-------|-----------|-------|
| `Components/` | 70 | `.drip` | composite UI blocks |
| `Elements/` | 23 | `.drip` | atomic UI fragments |
| `Patterns/` | 3 | `.drip` | `footer`, `header`, `sidebar` |
| `Assets/css/` | 5 | `.css` | `components`, `default`, `errors`, `tools`, `wiki` — CSS di luar design system |
| `Assets/css/ds/` | 21 | `.css` | numbered 1A–19 modular design system |
| `Assets/js/` | 2 | `.js` | `components.js`, `scaffolding.js` |

Total in scope: **124 files** (70 + 23 + 3 + 5 + 21 + 2). Additional folders `Errors/` (1), `Layouts/` (1), `Pages/` (0), `Templates/` (0) exist but are outside the stated scope. Counts were recounted from full disk data on 2026-11-28; earlier claims of 125 total and 6 `Assets/css` files were wrong.

### 8.2 Declared provenance (read from file headers, not assumed)

Of the 93 component/element files, **59 carry an explicit `Inspired by` line and 34 do not**. Extracted verbatim from full disk data (2026-11-28):

| Declared source | Components | Elements | Total |
|-----------------|-----------:|---------:|------:|
| shadcn/ui + Material Design | 17 | 13 | 30 |
| shadcn/ui + Radix UI | 14 | 1 | 15 |
| shadcn/ui + Bootstrap | 5 | 0 | 5 |
| shadcn/ui + GitHub | 2 | 1 | 3 |
| Lucide + Material Icons | 0 | 1 | 1 |
| Material Design only | 1 | 0 | 1 |
| shadcn/ui + cmdk | 1 | 0 | 1 |
| shadcn/ui + Embla | 1 | 0 | 1 |
| shadcn/ui + Tailwind | 1 | 0 | 1 |
| shadcn/ui + TanStack Table | 1 | 0 | 1 |
| No `Inspired by` line | 27 | 7 | 34 |

Earlier claims of "91 of 124 with line / 33 without" were wrong; they came from truncated tool output. Corrected from full data.

**Critical finding:** the dominant source is **shadcn/ui**, which is **not** among the ten repositories in section 1. The section-1 research therefore did not validate the primary provenance of the collection. That is a defect in the earlier research, recorded here explicitly.

### 8.3 Validation result per repository

Mapping the ten researched repositories against the declared sources:

| Repo (section 1) | Declared in collection? | What it validates | Coverage |
|------------------|------------------------|-------------------|----------|
| `twbs/bootstrap` | yes — 5 components + 1 element | `button-group`, `flex`, `grid`, `input-group`, `list-group` | partial |
| `jgthms/bulma` | no | HSL primitive approach — collection uses hex scales instead | not applicable |
| `google/material-design-lite` | yes — Material Design cited 32× | most components/elements | partial — MDL is one Material implementation, not the token spec |
| `foundation/yeti` | no | zero-build CSS idea | not applicable |
| `microsoft/fluentui` | no | token tiers | not applicable |
| `radix-ui/primitives` | yes — 14 components + 1 element | accordion, collapsible, context-menu, confirm-dialog, drawer, dropdown, hover-card, modal, nav-menu, popover, scroll-area, separator, tabs, tooltip, aspect-ratio | strong |
| `material-components/material-components-web` | no — Material cited but not MDC specifically | per-component package idea | indirect |
| `picocss/pico` | no | bare-tag styling | not applicable |
| `primer/css` | yes — "GitHub" cited 3× | `callout`, `code-block`, `kbd` | partial |
| `carbon-design-system/carbon` | no | DTCG token source | not applicable |

**Conclusion:** only 4 of the 10 researched repositories directly validate any declared source in the collection. Six were irrelevant to this specific collection. The research question should have been: *which repositories does the collection actually cite?* — then research those.

### 8.4 Repositories declared by the collection but absent from section 1

These are declared in the collection headers but were not part of the original ten-repo research. Star counts were verified via GitHub API on 2026-11-28.

| Repository | Declared by | Stars (verified 2026-11-28) | Archived | Why it matters |
|------------|-------------|----------------------------:|----------|----------------|
| `shadcn-ui/ui` | 55+ files | 129,254 | no | Primary source for nearly every component |
| `radix-ui/primitives` | 15 files | 19,612 | no | Already in section 1 — behavior/ARIA contract |
| `microsoft/fluentui` | token model | 20,437 | no | Already in section 1 — token tiers |
| `TanStack/table` | `data-table.drip` | 28,638 | no | Data-table behavior source |
| `lucide-icons/lucide` | `icon.drip` | 26,040 | no | Icon set source |
| `dip/cmdk` | `command-palette.drip` | 13,237 | no | `pacocoursey/cmdk` redirects here — command palette source |
| `primer/css` | 3 files | 13,063 | no | Already in section 1 — GitHub patterns |
| `davidjerleke/embla-carousel` | `carousel.drip` | 8,491 | no | Carousel behavior source (canonical repo) |

Star counts verified via `gh api repos/<owner>/<repo> --jq '{full_name, stars: .stargazers_count, archived, pushed_at}'`. `pacocoursey/cmdk` resolves to `dip/cmdk`; `embla-carousel/embla-carousel` does not exist — canonical is `davidjerleke/embla-carousel`.

### 8.5 CSS architecture validation

`Assets/css/ds/design-system.css` declares its own architecture verbatim:

> `RADYK DESIGN SYSTEM v2.0 — Modular Entry Point`
> `Architecture: Design Tokens → Base → Components → Utilities → A11y`
> `Supports: Light / Dark / High Contrast / Reduced Motion`

Numbered cascade order from the file:

| # | File | Role |
|---|------|------|
| 1 | `tokens.css` | Primitive → Semantic → Component (428 lines) |
| 2 | `base.css` | Reset & base (110) |
| 3 | `typography.css` | Type scale (—) |
| 4 | `layout.css` | Flex, Grid, Display, Position, Sizing (201) |
| 5 | `spacing.css` | Margin & Padding utilities (290) |
| 6 | `forms.css` | Forms & inputs (251) |
| 7 | `buttons.css` | Buttons (192) |
| 8 | `cards.css` | Cards & surfaces (84) |
| 9 | `tables.css` | Tables (110) |
| 10 | `badges.css` | Badges & tags (107) |
| 11 | `navigation.css` | Nav, breadcrumb, side-nav (155) |
| 12 | `modals.css` | Modals & dialogs (106) |
| 13 | `tooltips.css` | Tooltips & popovers (121) |
| 13b | `toggle.css` | Toggle (79) |
| 14 | `notifications.css` | Toasts + inline alerts (175) |
| 15 | `loading.css` | Spinners, dots, skeleton (117) |
| 16 | `animations.css` | Keyframes + utilities (101) |
| 17 | `utilities.css` | Radius, shadows, cursor, opacity (—) |
| 18 | `a11y.css` | SR-only, focus ring, reduced motion, high contrast (68) |
| 19 | `responsive.css` | Breakpoints sm–2xl (75) |

Validation against section 1:

| Claim in collection | Validated by | Verdict |
|---------------------|--------------|---------|
| Primitive → Semantic → Component | Spectrum, Primer, Carbon, Fluent (§2.5, §2.9, §2.10) | **valid** |
| Reset & base | Bootstrap, Yeti (§2.1, §2.4) | **valid** |
| Light / Dark | Bootstrap `[data-theme]`, Pico (§2.1, §2.8) | **valid** |
| High Contrast | Carbon accessibility-first (§2.10) | **valid** |
| Reduced Motion | Pico, Bootstrap (§2.1, §2.8) | **valid** |
| Numbered cascade order | Bootstrap `@layer` (§2.1) | **valid in intent** — collection uses import order, not `@layer` |
| `sr-only` + focus ring | Radix, Bootstrap (§2.1, §2.6) | **valid** |
| `prefers-reduced-transparency` | not found in the ten repos or the collection's declared sources | **unvalidated** — no external source identified |

### 8.6 Component coverage validation

Grouping the 70 components by the repository that validates them:

| Group | Components | Validated by | Count |
|-------|-----------|--------------|-------|
| Overlay / behavior | accordion, collapsible, confirm-dialog, context-menu, drawer, dropdown, hover-card, modal, nav-menu, popover, scroll-area, separator, tabs, tooltip | `radix-ui/primitives` | 14 |
| Data / layout | button-group, container, flex, grid, input-group, list-group, table, data-table | `twbs/bootstrap` (verified) + `TanStack/table` (28,638 stars, verified) | 8 |
| Form | fieldset, file-input, form-group, input-group, otp-input, password-input, range-slider, switch, tag-input | Material Design + `shadcn-ui/ui` (129,254 stars, verified) | 9 |
| Feedback | alert, callout, empty-state, empty, offline-indicator, toast, announce, cookie-consent | Material Design + `shadcn-ui/ui` (verified) | 8 |
| Content | ads, back-to-top, breadcrumb, calendar, card, carousel, chart, code-block, color-picker, color-swatch, copy-button, date-picker, iframe, item, kanban, map, markdown, qrcode, rating, rich-editor, signature-pad, social-links, stat-card, stepper, timeline, tree-view, tree-view-item, user-menu, video | mixed — `davidjerleke/embla-carousel` (8,491) for carousel, `lucide-icons/lucide` (26,040) for icon; rest need content-level provenance | 29 |
| Command | command-palette | `dip/cmdk` (13,237 stars, verified) | 1 |
| Separator | separator | Radix | 1 |

Elements (23): validated by Material Design (14), Radix (1), GitHub (1), Lucide (1), shadcn/ui (0 declared explicitly), no line (6).

### 8.7 Honest assessment

- The collection is **substantially broader** than the ten-repo research produced: 70 components vs 13 implemented, 23 elements vs 3, 21 CSS modules vs 3.
- The ten-repo research validated **4 repositories** that the collection actually cites. Six were off-target.
- The primary source (`shadcn-ui/ui`, 129,254 stars) was absent from the original ten-repo research. Its star count and archive status are now verified in §8.4; its component patterns still need a dedicated read of the upstream repository. This was the largest gap in the earlier research and is recorded here explicitly.
- The CSS architecture in the collection is **more complete** than NanoRADF's current `System.css`: 21 numbered modules vs 9 partials + 3 stylesheets.
- NanoRADF's current implementation must not be described as "the design system" — it is a partial subset of the curated collection.

### 8.8 Research completed and remaining work

Completed in this pass:

1. Star counts and archive status verified for all declared sources: `shadcn-ui/ui` (129,254), `TanStack/table` (28,638), `lucide-icons/lucide` (26,040), `dip/cmdk` (13,237), `davidjerleke/embla-carousel` (8,491), plus the three already in section 1.
2. Provenance extracted from file headers — 59 of 93 component/element files carry an explicit `Inspired by` line; 34 do not. Corrected from full disk data.

Remaining work (not yet performed):

1. For the 33 files with no `Inspired by` line, determine provenance by reading content — do not guess.
2. Read the full body of all 124 files and map each to a NanoRADF path, marking SSR-safe vs JS-dependent.
3. Decide which of the 70 components can be adopted as pure SSR fragments and which require progressive enhancement.
4. Update this document with those results before any further implementation.

No implementation was performed as part of this validation section.

---

## 9. Implementation plan derived from the requirement list

### 9.1 Rule

The curated collection is a **requirement file list only**. Its files define which files are needed and what each must support; NanoRADF implementations are written independently from the design patterns of the verified repositories in sections 1 and 8.4, using NanoRADF token names, NanoRADF dot-notation, and SSR-first markup. Provenance of any future implementation is claimed only after the source is read before writing and the result is verified.

### 9.2 Requirement → source mapping

| Requirement group | Count | Design source (verified) | NanoRADF target |
|-------------------|------:|--------------------------|-----------------|
| Components — overlay/behavior | 14 | `radix-ui/primitives` (19,612) + `shadcn-ui/ui` (129,254) | `src/Views/Components/*.html` |
| Components — data/layout | 8 | `twbs/bootstrap` (175,141) + `TanStack/table` (28,638) | `src/Views/Components/*.html` |
| Components — form | 9 | Material Design + `shadcn-ui/ui` | `src/Views/Components/*.html` |
| Components — feedback | 8 | Material Design + `shadcn-ui/ui` | `src/Views/Components/*.html` |
| Components — content | 29 | mixed: `davidjerleke/embla-carousel` (8,491), `lucide-icons/lucide` (26,040), `dip/cmdk` (13,237), `shadcn-ui/ui` | `src/Views/Components/*.html` |
| Components — command | 1 | `dip/cmdk` | `src/Views/Components/CommandPalette.html` |
| Components — separator | 1 | `radix-ui/primitives` | `src/Views/Components/Separator.html` |
| Elements | 23 | Material Design (14), Radix (1), GitHub (1), Lucide (1), shadcn/ui (6) | `src/Views/Elements/*.html` |
| Patterns | 3 | Bootstrap + shadcn/ui | `src/Views/Patterns/*.html` |
| CSS modules | 21 | Bootstrap `@layer`, Spectrum/Primer tiers, Pico bare-tag, Carbon a11y | `src/Views/Tokens/` only |
| JS enhancements | 2 | Radix ARIA contract, progressive enhancement only | `src/Views/Scripts/` |

### 9.3 Naming translation (collection → NanoRADF)

| Collection | NanoRADF | Reason |
|-----------|----------|--------|
| `.drip` | `.html` | Drip engine accepts `.html` only (`docs/RULES.md` §1) |
| `variant="destructive"` | `tone="bad"` | NanoRADF semantic tokens use `--bad-*` |
| `variant="success"` | `tone="ok"` | NanoRADF uses `--ok-*` |
| `variant="warning"` | `tone="warn"` | NanoRADF uses `--warn-*` |
| `--primary-*` | `--signal-*` | NanoRADF primitive brand scale |
| `--gray-*` | `--n-*` | NanoRADF neutral scale |
| `--danger-*` | `--bad-*` | NanoRADF semantic feedback |
| `@php $x = $x ?? ...` | `{{$x ?? 'default'}}` | Drip templates contain no PHP business logic (`docs/RULES.md` §2) |
| `uniqid('prefix_')` | caller-supplied `id` | No random IDs in SSR output; deterministic markup |
| `data-state`, `aria-expanded` | kept | ARIA contract from Radix, valid in SSR |

### 9.4 SSR-safe vs JS-enhanced split

| Class | Files | Rule |
|-------|-------|------|
| SSR-pure | ~70 | Full markup + state rendered by PHP; no JS needed |
| SSR + progressive enhancement | ~30 | Markup and ARIA rendered server-side; vanilla JS only adds interaction (open/close, copy, toggle) |
| Rejected | 0 | No React, no web components, no shadow DOM, no npm |

### 9.5 Execution order

1. CSS foundation: 21 modules mapped to `Tokens/`, `@layer` order preserved.
2. Elements: 23 atomic fragments, token-only, no JS.
3. Components: 70 fragments, grouped by source, ARIA contract from Radix.
4. Patterns: 3 fragments (`Header`, `Footer`, `Sidebar`).
5. Scripts: 2 vanilla JS files for progressive enhancement only.
6. Tests: extend `tests/tokens_check.php` + add `tests/components_check.php` for render/escaping/ARIA.
7. Docs sync: PLAN, PRD, REPORT, CHANGELOG, README, DESIGN-SYSTEM.

### 9.6 Verification gate

- Every fragment resolves via `System.<role>.<name>`.
- Every `var()` reference resolves to a declared token.
- No component references a primitive token directly.
- Every fragment escapes data via `{{ }}`; raw `{{! !}}` only for vetted caller content.
- ARIA attributes present on interactive fragments.
- All 8 existing suites stay green; new component check passes.

## 10. Requirement matrix — per-file needs vs current implementation

### 10.1 Method

Every one of the 124 requirement files was read for its **needs** (variables, variants, states, ARIA, JS hooks). This section records the gap between what the collection requires and what NanoRADF at `195fdf5` implements. It is an analysis document only — no files were changed as part of this section.

### 10.2 Element requirements (23 files)

| Requirement | Needs found in collection | NanoRADF current | Gap |
|-------------|---------------------------|------------------|-----|
| `aspect-ratio` | `$ratio`, `$as`, `$class`, `$id`, slot | `Aspect-Ratio.html`: `$ratio`, `$content` | no `$as`, no slot |
| `avatar` | `$src`, `$alt`, `$fallback`, `$size` (6), `$variant` (circle/rounded/square), `$status` (4), `$href`, `$initials` auto-derived, `onerror` fallback | `Avatar.html`: `$src`, `$alt`, `$fallback`, `$size` | no variant, status, href, initials, fallback behavior |
| `badge` | `$label`, `$variant` (7), `$size` (3), `$dot`, `$dotColor`, `$href`, `$icon`, `$iconPosition` | `Badge.html`: `$tone`, `$text` | no size, dot, href, icon |
| `button` | `$variant` (7), `$size` (6), `$disabled`, `$as` (button/a), `$href`, `$loading` + spinner, `$type`, slot | `Button.html`: `$tone`, `$type`, `$disabled`, `$label` | no size, as/href, loading, slot |
| `checkbox` | `$label`, `$checked`, `$disabled`, `$required`, `$value`, `$name`, `$id`, `$indeterminate`, `$hint`, `$error`, `aria-describedby` | `Checkbox.html`: `$label`, `$name`, `$checked` | no indeterminate, hint, error, ARIA link |
| `chip` | `$label`, `$variant` (7), `$size` (3), `$dismissible`, `$icon`, `$avatar`, `$href`, `$selected`, `$disabled`, `aria-pressed` | `Chip.html`: `$label`, `$tone`, `$removable` | no avatar, href, selected, size |
| `gravatar` | `$email` → md5 hash, `$size`, `$default`, `$rating`, `srcset` 2x | `Gravatar.html`: `$src`, `$alt`, `$size` | no hash derivation, default/rating, srcset |
| `icon` | `$name`, `$size` (6), `$strokeWidth`, `$label`, `$color`, inline SVG path map | `Icon.html`: placeholder span, `data-icon` | **no SVG paths** — not a real icon |
| `image` | `$src`, `$alt`, `$srcset`, `$sizes`, `$objectFit`, `$rounded`, `$caption`, `$aspectRatio`, `$fallback` onerror | `Image.html`: `$src`, `$alt`, `$loading` | no srcset, fit, caption, ratio, fallback |
| `input` | `$type`, `$label`, `$placeholder`, `$error`, `$hint`, `$disabled`, `$required`, `$readonly`, `$autocomplete`, `$icon`, `$iconPosition`, `aria-describedby` | `Input.html`: `$name`, `$label`, `$type`, `$value`, `$placeholder`, `$error` | no hint, readonly, autocomplete, icon, ARIA link |
| `kbd` | `$keys` (array or `a+b` string), `$size` (3), separator `+` | `Kbd.html`: `$keys` | no string split, size, separator |
| `label` | `$for`, `$label`, `$required`, `$hint`, `$id` | `Label.html`: `$for`, `$label`, `$required` | no hint, id |
| `progress` | `$value`, `$max`, `$variant` (5), `$size` (3), `$showLabel`, `$indeterminate`, `$label` | `Progress.html`: `$value`, `$max`, `$label` | no variant, size, indeterminate, label display |
| `radio` | same as checkbox | `Radio.html`: `$label`, `$name`, `$value`, `$checked` | no disabled, required, hint, error |
| `search-box` | `$placeholder`, `$name`, `$value`, `$class` | `Search-Box.html`: same | complete |
| `select` | `$options`, `$label`, `$placeholder`, `$error`, `$hint`, `$disabled`, `$required` | `Select.html`: `$name`, `$options`, `$label`, `$selected` | no placeholder, error, hint, states |
| `skeleton` | `$variant` (8), `$width`, `$class` | `Skeleton.html`: `$variant`, `$width` | fewer variants |
| `skip-link` | `$href`, `$label`, `$class`, `$id` | `Skip-Link.html`: `$href`, `$label` | no class/id |
| `spinner` | `$size` (5), `$variant` (4: default/dots/pulse/bar) | `Spinner.html`: `$size`, `$label` | no variant |
| `sr-only` | `$content`, `$as`, `$class`, `$id` | `Sr-Only.html`: `$content`, `$as` | no class/id |
| `textarea` | `$label`, `$placeholder`, `$error`, `$hint`, `$rows` | `Textarea.html`: `$name`, `$label`, `$value`, `$rows` | no placeholder, error, hint |
| `theme-toggle` | `$class`, `$size` (3), `$label`, `$id` | `Theme-Toggle.html`: `$label` | no size, class, id |
| `truncate` | `$text`, `$maxLines`, `$as`, `$class` | `Truncate.html`: `$text`, `$maxLines` | no as/class |

**Element verdict:** 2 complete, 21 partial. The largest functional gap is `icon` — NanoRADF renders an empty span, not an SVG.

### 10.3 Component requirements (70 files)

Grouped by complexity found in the collection:

| Group | Files | Needs found | NanoRADF current | Gap |
|-------|-------|-------------|------------------|-----|
| **SSR-pure, simple** (~25) | `ads`, `announce`, `back-to-top`, `callout`, `code-block`, `container`, `empty`, `empty-state`, `fieldset`, `flex`, `grid`, `iframe`, `item`, `list-group`, `markdown`, `offline-indicator`, `qrcode`, `rating`, `separator`, `social-links`, `stat-card`, `stepper`, `table`, `timeline`, `video` | variants, sizes, ARIA, captions | basic markup, few variants | variant/size coverage incomplete |
| **SSR-pure, stateful** (~20) | `alert`, `breadcrumb`, `button-group`, `calendar`, `card`, `chart`, `color-swatch`, `copy-button`, `data-table`, `date-picker`, `file-input`, `file-upload`, `form-group`, `input-group`, `otp-input`, `pagination`, `password-input`, `range-slider`, `switch`, `tag-input` | dismissible, sortable, paginated, selectable, strength meter, dual range, suggestions | static markup only | interactive states not represented |
| **SSR + JS enhancement** (~25) | `accordion`, `carousel`, `collapsible`, `command-palette`, `confirm-dialog`, `context-menu`, `drawer`, `dropdown`, `hover-card`, `kanban`, `map`, `modal`, `nav-menu`, `popover`, `rich-editor`, `scroll-area`, `signature-pad`, `tabs`, `toast`, `tooltip`, `tree-view`, `tree-view-item`, `user-menu`, `cookie-consent`, `theme-toggle` | `data-*` hooks, focus trap, keyboard nav, open/close, outside-click, Escape, autoplay, drag | markup + ARIA only; `Components.js` handles generic `[aria-expanded]` and copy/theme | **no focus trap, no Escape, no outside-click, no keyboard nav, no drag, no autoplay** |

**Component verdict:** 70/70 files exist, but ~45 are structurally thinner than the requirement and ~25 lack the JS behavior contract.

### 10.4 Pattern requirements (3 files)

| Requirement | Needs found | NanoRADF current | Gap |
|-------------|-------------|------------------|-----|
| `header` | `$brand`, `$nav`, `$variant`, `$sticky`, `$mobileBreakpoint`, `$showSearch`, `$showThemeToggle`, `$userMenu`, `$ctaButtons` | `$brand`, `$nav`, `$sticky` | no variant, search, theme toggle slot, user menu, CTA |
| `footer` | `$sections`, `$copyright`, `$logo`, `$description`, `$socialLinks`, `$bottomLinks`, `$variant` | `$sections`, `$copyright` | no logo, description, social, bottom links, variant |
| `sidebar` | `$items`, `$collapsible`, `$collapsed`, `$variant`, `$position`, `$footer`, `$logo`, `$logoHref` | `$items`, `$collapsed`, `$logo`, `$logoHref`, `$footer` | no collapsible behavior, variant, position |

### 10.5 CSS requirements (26 files)

| Collection module | Lines | NanoRADF equivalent | Gap |
|-------------------|------:|---------------------|-----|
| `ds/tokens.css` | 428 | `Tokens/*.css` (9 files) | NanoRADF has fewer primitives; no `--primary-*`/`--gray-*` aliases |
| `ds/base.css` | 110 | `Tokens/reboot.css` | comparable |
| `ds/typography.css` | — | `Tokens/typography.css` | comparable |
| `ds/layout.css` | 201 | `Tokens/utilities.css` | NanoRADF lacks position/sizing utilities |
| `ds/spacing.css` | 290 | `Tokens/utilities.css` | NanoRADF has ~20 spacing classes vs ~290 lines |
| `ds/forms.css` | 251 | `Tokens/forms.css` | NanoRADF lacks validation state classes |
| `ds/buttons.css` | 192 | `Tokens/components.css` | NanoRADF has 3 button tones vs 7 variants + 6 sizes |
| `ds/cards.css` | 84 | `Tokens/components.css` | comparable |
| `ds/tables.css` | 110 | `Tokens/components.css` | NanoRADF lacks striped/bordered/compact/sticky |
| `ds/badges.css` | 107 | `Tokens/components.css` | NanoRADF has 5 tones vs 7 variants + 3 sizes |
| `ds/navigation.css` | 155 | `Tokens/navigation.css` | comparable |
| `ds/modals.css` | 106 | `Tokens/components.css` | no modal/dialog CSS in NanoRADF |
| `ds/tooltips.css` | 121 | `Tokens/components.css` | no tooltip CSS |
| `ds/toggle.css` | 79 | `Tokens/components.css` | no switch CSS |
| `ds/notifications.css` | 175 | `Tokens/feedback.css` | partial |
| `ds/loading.css` | 117 | `Tokens/feedback.css` | partial |
| `ds/animations.css` | 101 | `Tokens/motion.css` | no keyframe library |
| `ds/utilities.css` | — | `Tokens/utilities.css` | partial |
| `ds/a11y.css` | 68 | `Tokens/accessibility.css` + `Reboot.css` | comparable |
| `ds/responsive.css` | 75 | `Tokens/media.css` + `Utilities.css` | partial |
| `ds/design-system.css` | 49 | `Tokens/System.css` | comparable architecture |
| `components.css` | — | `Tokens/components.css` | partial |
| `default.css`, `errors.css`, `tools.css`, `wiki.css` | — | none | **4 app-level stylesheets not represented** |

### 10.6 JS requirements (2 files)

| Collection | Needs found | NanoRADF current | Gap |
|-----------|-------------|------------------|-----|
| `components.js` | Modal, Drawer, Dropdown, Tabs, Accordion, Tooltip, Toast, Collapsible, Carousel, BackToTop, ThemeToggle, OfflineIndicator — each with class toggles, focusable query, Escape/outside-click | `Scripts/Components.js`: generic `[aria-expanded]`, copy, theme | **10 behaviors missing** |
| `scaffolding.js` | scaffolding helpers | `Scripts/Scaffolding.js`: offline + carousel | partial |

### 10.7 SSR-pure vs JS-enhanced classification

| Class | Count | Files |
|-------|------:|-------|
| SSR-pure | ~45 | simple + stateful components, all elements, all patterns, all CSS |
| SSR + JS enhancement | ~25 | overlay/behavior components listed in §10.3 |
| JS-only behavior | 2 | `Scripts/Components.js`, `Scripts/Scaffolding.js` |
| Rejected (NFR-1) | 0 | no React, no web components, no npm |

### 10.8 Honest conclusion

The 128 files committed in `a6db5a5` satisfy the **file list** but not the **functional requirement** of the collection. Roughly 21/23 elements, 45/70 components, 3/3 patterns, 12/26 CSS modules, and 2/2 JS files are thinner than the requirement. The commit should be treated as a skeleton, not a completed design system.

No further implementation was performed while writing this matrix.

---

## 11. Error prevention guide — 25 mistakes from Phase 8

This section is a **mandatory checklist** derived from a full audit of Phase 8 on 2026-11-28. It exists so the same mistakes are not repeated. Read it before touching any design-system file.

### 11.1 The 25 original mistakes + 3 found during deep re-check

#### CRITICAL (6)

| # | Mistake | Evidence | Prevention |
|---|---------|----------|------------|
| C1 | `@import` placed **inside** `@layer name { ... }` blocks. MDN: `@import` must appear before all rules except `@charset` and layer-creating statements. Browsers ignore it. | `Tokens/System.css` — 8 imports inside 4 layer blocks | Use `@import url("x.css") layer(tokens);` at the top of the file. Never nest `@import` in a layer block. |
| C2 | `System.css` never enqueued. `apps/Config/AssetsConfig.php` has `enqueue_styles => ['style']` only. | `AssetsConfig.php:31` | After adding any stylesheet, grep `enqueue_styles` and confirm the file is queued or imported by a queued file. |
| C3 | JS selector `[data-carousel-prev]` / `[data-carousel-next]` but HTML uses `.carousel__prev` / `.carousel__next`. Carousel dead. | `Scaffolding.js` vs `Carousel.html` | Cross-check every JS selector against HTML with a script. Both sides must use the same hook. |
| C4 | REPORT claimed "render probe confirms escaping + ARIA" while the probe output showed `aria-current=&quot;page&quot;` — a broken attribute. | `REPORT.md:57` vs probe output | Save probe output. Read it. Compare claim to output. Never claim verified from exit code alone. |
| C5 | ~140 CSS classes used by fragments have no style definition. Accordion, carousel, dialog, drawer, dropdown, tabs, tooltip, kanban are bare HTML. | class-usage scan: 155 undefined (incl. false positives) | After writing fragments, scan `class="..."` against all CSS. Every class must resolve. |
| C6 | Three-layer CSS failure: invalid `@import` + no enqueue + no theme import. CSS never reaches browser at all. | `System.css`, `AssetsConfig.php`, `public/themes/*` | Verify the full chain: file → import syntax → enqueue → rendered `<link>` → browser. |

#### HIGH (10)

| # | Mistake | Evidence | Prevention |
|---|---------|----------|------------|
| H1 | Duplicate `h1-h6`, `h1..h6`, `p` rules in `Tokens/typography.css` and `Tokens/typography.css`. | both files | One rule, one owner. Tokens file = variables only; Styles file = element rules. |
| H2 | Duplicate `input/select/textarea/button` styling in `Tokens/form.css:27` and `Tokens/forms.css`. | both files | Same as H1. |
| H3 | Duplicate `code/kbd/pre/samp` monospace in `Reboot.css:62` and `Tokens/typography.css:64`. | both files | Same as H1. |
| H4 | Static IDs hardcoded: `dlg-title`, `dlg-desc`, `modal-title`, `hc-content`, `tip-content`, `acc-{{$i}}`, `tab-{{$i}}`. Two instances → duplicate IDs → broken ARIA. | 6 fragments | Accept `$id` from caller; derive `aria-labelledby`/`aria-controls` from it. Never hardcode an ID in a reusable fragment. |
| H5 | 22 files use `{{(cond) ? ' aria-current="page"' : ''}}`. Drip escapes the quotes → `aria-current=&quot;page&quot;`. | render probe Header | Use `@if($cond) aria-current="page" @endif` — literal markup outside `{{ }}`. |
| H6 | `event.target.closest()` without `instanceof Element` guard — text nodes/SVG throw. | `Components.js:15` | Guard: `var el = event.target instanceof Element ? event.target : event.target.parentElement;` |
| H7 | Commit `a6db5a5` bypassed `>push` protocol (5-suite order, per-file changelog, `git log -3` confirmation). | git log | Follow `>push` exactly. |
| H8 | `PRD.md` not updated for Phase 8 — 5-file sync rule broken. | grep PRD: no Phase 8 | Before commit, check PLAN/PRD/REPORT/CHANGELOG/README all updated. |
| H9 | 4 duplicate fragments in Components and Elements: `Checkbox`, `Radio`, `Select`, `Textarea`. Different content under two names. | folder diff | One file per concept. Collection puts form primitives in Elements — follow that. |
| H10 | `assets_check.php:75` checks `/system/css/system.css` **resolves**, not that it renders/enqueues. PASS is misleading. | `assets_check.php:75` | Test the rendered `<link>` output, not just file existence. |

#### MEDIUM / LOW (9)

| # | Mistake | Evidence | Prevention |
|---|---------|----------|------------|
| M1 | `DESIGN-SYSTEM.md` claimed "91 of 124 files carry Inspired by" — actual **59 of 93** component/element files (34 without). Number taken from truncated grep output. | full disk recount 2026-11-28 | Count from full data, never from truncated tool output. |
| M2 | Provenance table numbers off: Material 30 (not 31), Radix 15 (not 14), no-line 34 (not 33), with-line 59 (not 91). | full disk recount | Same as M1. |
| M3 | Claim that `Assets/css` = 6 and total = 125 was wrong — actual **5** and **124**. The original report was right; the "correction" was not. | full disk recount | Same as M1. Verify before "correcting" a number. |
| M4 | `Reboot.css` sets `scroll-behavior: smooth` by default — unrequested design decision. | `Reboot.css:14` | Do not add behavior not in the requirement. |
| M5 | `CLAUDE.md:22` PHP path stale (`8.3.36+0`, machine has `8.3.37+4`) — not reported to user. | `Test-Path` | Report stale bible entries; do not silently work around them. |
| M6 | `learnings.json` `last_updated` set before actual commit time. | timestamps | Use actual time. |
| M7 | `docs/DESIGN-SYSTEM.md` §9.1/§10.1 claim "no code copied" for `a6db5a5`, but collection contents were read **after** the commit. Unverifiable claim. | chronology | Read source before writing. Never claim provenance you cannot prove. |
| M8 | 0 `@include` across 98 fragments — no composition/DRY. Components duplicate element markup. | grep `@include(` = 0 | Compose from elements; do not repeat markup. |
| M9 | JS hook `data-offline-indicator` missing in `Offline-Indicator.html` — indicator dead. | JS↔HTML cross-check | Same as C3. |
| M10 | Deep check 3: 4 fragments still render `&quot;` in ARIA attributes (`File-Input`, `Pagination`, `Elements/Input`, `Patterns/Header`). H5 count of 22 was based on one ternary pattern only. | render probe with sample data | Render **all** fragments with sample data; grep output for `&quot;` inside attributes. Never rely on a single grep pattern. |
| M11 | Commit `a6db5a5` swept a pre-existing user edit to `.supports` (9+/14-) via `git add -A`. | `git show --stat a6db5a5 -- .supports` | Before `git add -A`, inspect `git status` + `git diff`. Separate user changes from task changes. If overlap, stop and ask. |
| M12 | `REPORT.md:32` claims "6 files" SSR fragments; actual 98 (70+25+3). | `REPORT.md:32` vs disk count | Recount from disk before writing any number into docs. Never copy a number from an earlier phase entry. |
| M13 | Put 8 design-system CSS files in `src/Views/Styles/`. Owner fact: `Styles/` is for CSS **outside** the design system; all design-system CSS belongs in `Tokens/` only. No question asked, no decision recorded in PLAN/CHANGELOG. | `src/Views/Styles/*.css` (Phase 8 commit) vs owner fact 2026-11-28 | Design-system CSS → `Tokens/` only. CSS outside the design system (e.g. `Styles/Errors/core.css`) → `Styles/`. If a file's category is unclear, **stop and ask**. |

### 11.2 Mandatory pre-flight before ANY design-system edit

1. **Read the bible** — `CLAUDE.md`, `docs/RULES.md`, this section. Not from memory.
2. **Read the target file fully** — not grep, not diff. Full content from disk.
3. **Write the requirement matrix first** — file → needs → SSR/JS → source repo. Wait for confirmation before coding.
4. **Check CSS chain** — `@import` at top with `layer()` syntax → enqueue in `AssetsConfig` → rendered `<link>` → browser.
5. **Check class coverage** — every `class="..."` in fragments must have a CSS rule.
6. **Check JS↔HTML hooks** — every JS selector must exist in HTML and vice versa.
7. **Check ARIA** — no hardcoded IDs; no quoted attributes inside `{{ }}`; use `@if` for conditional attributes.
8. **Render every fragment** with empty data AND with sample data. Read the output. Look for `&quot;` in attributes.
9. **Count numbers from full data** — never from truncated tool output.
10. **Sync 5 docs** — PLAN, PRD, REPORT, CHANGELOG, README.
11. **Follow `>push`** — 5-suite first, per-file changelog, `git add -A`, commit, push, `git log -3`.
12. **Claim only what evidence shows** — save probe output; compare claim to output; if they disagree, report failure.

### 11.3 Red flags — stop immediately if you see these

- `&quot;` inside any HTML attribute in rendered output.
- `@import` indented inside `{ }`.
- A CSS class in a fragment with no matching rule.
- A JS `data-*` selector with no matching HTML attribute.
- Two fragments with the same `id="..."` literal.
- A test that checks file existence but claims to check rendering.
- A number in a document that came from a truncated grep.
- A commit made before the requirement matrix was written.
- A design-system CSS file in `Styles/`, or a non-design-system CSS file in `Tokens/`. Stop. Ask.

---

