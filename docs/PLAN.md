# PLAN — NanoRADF

Goal: deliver the v1 framework defined in `docs/PRD.md` — two-mode (SSR frontend / headless backend), zero-dependency PHP, module-based, server-to-server communication.

Constraints: PHP 8.1+, zero runtime deps, English docs, dev URL `http://nanoradfw.local`, no new top-level dirs without a recorded decision.

Definition of done (v1): all PRD acceptance criteria pass; smoke checks recorded in `docs/REPORT.md`; docs in sync; release only via `>release`.

## Decision log

| ID | Date | Decision | Rationale |
|----|------|----------|-----------|
| D1 | 2026-11-20 | Dev URL = `http://nanoradfw.local`, docroot `public/`, PHP 8.3.36 (LocalWP), MySQL `:10019`. | LocalWP environment already provisioned. |
| D2 | 2026-11-20 | `sql/local.sql` is a leftover local dump → gitignored, not deleted. | Non-destructive; out of project scope. |
| D3 | 2026-11-20 | Fix kernel autoloader defects now (not docs-only). | Boot path fatals on first class load; blocking. |
| D4 | 2026-11-20 | Project docs in English; `CLAUDE.md` keeps its mixed ID/EN voice. | User choice. |
| D5 | 2026-11-20 | Plan first, execution in later phases/tasks. | User choice: no implementation beyond D3 fix in this change set. |
| D6 | 2026-11-20 | `app.modules` env toggle: `false` **or key absent (default)** → flat `apps/` layout — `Config/`, `Services/`, `Controllers/`, `Model/`, `Views/` directly under `apps/`, no `apps/Modules/`, no `Manifest.json`. `true` → current modular layout. Namespace = single PSR-4 root `App\` → `apps/` mirroring folders (one mapping serves both modes). | User: missing key → false; flat scope = everything except manifest; namespace chosen by PSR-4 best practice (also closes the `apps/Config`/`apps/Services` autoload gap). |
| D7 | 2026-11-20 | **All test files live in `/tests`** (new tracked top-level dir); smoke checks never in `public/` or `src/`. Bootstrap smoke check: `tests/smoke_check.php` (self-contained, any CWD). | User directive; keeps the web root clean. Recorded per the no-new-top-directory rule. |
| D8 | 2026-11-20 | `app.mode` env toggle: `bone` (headless API backend only), `face` (SSR frontend → headless backend, server-to-server), `full` (both; default). Selectable before app development. Pair of FR-12 and Phase 4; `.env` carries the key with a docblock. | PRD §1 already defines the two selectable modes; this makes the selection an explicit env key instead of an implicit convention. |

## Status legend

✅ done · 🔵 in progress · ⚠️ pending · ⛔ blocked

## Phases

### Phase 0 — Bootstrap & kernel ✅
- Entry chain `public/index.php` → `nanoradf.php` → `.config` → `.supports` → `.structures` → `Boot::initiate()`.
- Global helpers (`is`, `interlace`, `pathify`, `namespacify`, `urlify`, `__env`, `__server`, `cast_value`, `env_clear_cache`, `read_directories`).
- Path/namespace constants, `is_dir` guarded; PSR-4 autoloader.
- **2026-11-20 fixes (D3):** undeclared `self::$nssys`/`self::$nsmod` → global constants `nssys`/`nsappmod`; undefined `modulesf` → `modulesfapp`. Fail-before/pass-after recorded in `docs/REPORT.md`.
- Verification: boot smoke check + autoload resolution of `System\Helper\Utilities` and `App\Modules\Welcome\Controllers\WelcomeController` → PASS (exit 0).

### Phase 0b — `app.modules` layout toggle ⚠️
- **Depends on:** Phase 0. **Owner:** main thread. **Decision:** D6.
- Gate structure registration in `.structures` on `__env ('app.modules')` (missing key → `false`):
  - `true` → keep modular constants (`modulesfapp`, per-module folders + `Manifest.json` contract), still `is_dir` guarded.
  - `false` → do not register `apps/Modules/`; register `Config/`, `Services/`, `Controllers/`, `Model/`, `Views/` directly under `apps/` (skip missing dirs as today).
- Autoloader: replace the `App\Modules` special case with a single PSR-4 root `App\` → `apps/` — `App\Modules\Welcome\X` still resolves to `apps/Modules/Welcome/X.php` when enabled; `App\Controllers\X`, `App\Config\X`, `App\Services\X` resolve flat. `System\` → `src/` unchanged.
- Current `.env` carries `app.modules=false` (working default); the key itself stays gitignored — semantics documented here and in PRD FR-11.
- Verification: bootstrap check **both** ways — `app.modules=false` (flat constants defined, no `modulesfapp`, `App\Controllers\...` autoload resolves) and `app.modules=true` (modular constants + `App\Modules\Welcome\...` resolves, backward compatible).

### Phase 1 — Routing & controllers ⚠️
- **Depends on:** Phase 0, Phase 0b (controller/model/view locations are defined there).
- **Owner:** main thread.
- Implement `src/Kernel/Router.php` (method + path matching, params, 404 fallthrough) and `src/Kernel/Controller.php` (request lifecycle, response helpers, render hook).
- Wire `apps/Config/Routes.php` as the route table consumed by the router.
- Base controllers `PublicController` / `PrivateController` / `ApiController` (surface-specific auth/response rules).
- Verification: CLI route-match checks (match/miss/param cases) + `http://nanoradfw.local` returns a controller response, unknown path → 404 handler.

### Phase 2 — View system & assets ⚠️
- **Depends on:** Phase 1. **Owner:** main thread.
- Blade-shaped renderer: compose `Layouts/` ← `Patterns/` ← `Components/`/`Elements/`, module `Views/*.php`, escaping helper in `src/Helper/Utilities.php`.
- `src/Services/AssetsService.php` + theme registration (`public/themes/default`, `src/Themes/admin`), design tokens from `src/Views/Tokens/System.css`.
- Verification: rendered page with layout+pattern composition; escaping test (malformed input stays inert HTML).

### Phase 3 — Welcome module end-to-end ⚠️
- **Depends on:** Phase 2. **Owner:** main thread.
- Fill `apps/Modules/Welcome/` (Manifest, Config, Controller, Model, Service, Views) and register its route.
- Verification: `/welcome` over HTTP renders through the view composer; removing the module route yields 404.

### Phase 4 — Headless mode & server-to-server client ⚠️
- **Depends on:** Phase 1 (parallel-safe with Phase 2–3). **Owner:** main thread.
- `ApiController` conventions (JSON envelope, status codes, error shape), backend route group.
- Frontend→backend PHP HTTP client (cURL/stream) with status + schema validation before rendering.
- Verification: SSR page renders data fetched server-side; no browser `fetch` for data (grep evidence).

### Phase 5 — Session & auth token storage ⚠️
- **Depends on:** Phase 4. **Owner:** main thread.
- `$_SESSION`-only access/refresh token storage; cookie flags `HttpOnly`/`SameSite=Strict`/`secure` (prod); CSRF nonce for state-changing routes; auth guards on `PrivateController`/`ApiController`.
- Verification: token never appears in client storage (grep); unauthenticated private route rejected; nonce check blocks forged POST.

### Phase 6 — Errors, polish, docs sync ⚠️
- **Depends on:** Phase 3, 4. **Owner:** main thread.
- 404/500 pages from `src/Views/Errors/`, `.env` defaults documented, README final pass, docs sync review.
- Verification: 404/500 render themed pages; docs cross-reference check (no dangling file refs).

### Phase 7 — Audit & release readiness ⚠️
- **Depends on:** Phase 1–6. **Owner:** main thread via `>audit` then `>release` (user-invoked).
- Line-by-line audit, tooling evidence, `docs/REPORT.md` entry, changelog promotion only when release is agreed.
- Verification: audit ledger complete; release workflow rules satisfied.

## Parallelism map

| Wave | Phases | Starts when |
|------|--------|-------------|
| 1 | 0 | — (complete) |
| 2 | 0b | Phase 0 done |
| 3 | 1 | Phase 0b done |
| 4 | 2, 4 | Phase 1 done (2 and 4 are independent) |
| 5 | 3, 5 | Phase 2 / Phase 4 done |
| 6 | 6 | Phases 3 + 4 done |
| 7 | 7 | everything above done, user invokes `>audit`/`>release` |

## Track log

| Date | Result | Evidence |
|------|--------|----------|
| 2026-11-20 15:11 | **Plan == code, no drift.** Phase 0 re-verified (boot smoke + autoload: `BOOT-LOADED`, `autoload sys=YES mod=YES`, exit 0). Phases 1–6: every target file still a 0-byte scaffold (`Kernel/Router.php`, `Kernel/Controller.php`, Api/Private/Public controllers, `apps/Config/Routes.php`, `Helper/Utilities.php`, `AssetsService.php`, all `src/Views/*`, Welcome module, themes) → ⚠️ statuses correct, no phase advanced. Open risks #1–#3 unchanged. Working tree clean at `24404fa`; no `origin` remote → push skipped. | `git status`, per-file size inventory, `boot_check.php` |

## Open risks

- `apps/Services` / `apps/Config` classes have no namespace mapping today — closed **by design** in Phase 0b (single `App\` → `apps/` root); confirm with the both-mode verification there.
- `read_directories()` is currently uncalled (entry no longer declares `$directories`) — decide keep-or-remove in Phase 1, record in REPORT/CHANGELOG.
- No test framework: smoke checks are manual until a test runner is agreed (candidate: plain-PHP assert scripts, zero deps).
