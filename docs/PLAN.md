# PLAN — NanoRADF

Goal: deliver the v1 framework defined in `docs/PRD.md` — two-mode (SSR frontend / headless backend), zero-dependency PHP, module-based, server-to-server communication.

Constraints: PHP 8.1+, zero-dependency independent framework (no Composer/packages, no plugins; PSR-4 via `System\Kernel\Boot::autoloader`), SSR frontend / headless backend two-mode, module-based, server-to-server communication.

Definition of done (v1): all PRD acceptance criteria pass; smoke checks recorded in `docs/REPORT.md`; docs in sync; release only via `>release`.

## Decision log

| ID | Date | Decision | Rationale |
|----|------|----------|-----------|
| D1 | 2026-11-20 | Dev URL = `http://nanoradfw.local`, docroot `public/`, PHP 8.3.36 (LocalWP), MySQL `:10019`. | LocalWP environment already provisioned. |
| D2 | 2026-11-20 | `sql/local.sql` is a leftover local dump → gitignored, not deleted. | Non-destructive; out of project scope. |
| D3 | 2026-11-20 | Fix kernel autoloader defects now (not docs-only). | Boot path fatals on first class load; blocking. |
| D4 | 2026-11-20 | Project docs in English; `CLAUDE.md` keeps its mixed ID/EN voice. | User choice. |
| D5 | 2026-11-20 | Plan first, execution in later phases/tasks. | User choice: no implementation beyond D3 fix in this change set. |
| D6 | 2026-11-20 | `app.modules` env toggle: `false` **or key absent (default)** → flat `apps/` layout; `true` → modular layout. Namespace = single PSR-4 root `App\` → `apps/` mirroring folders (one mapping serves both modes). | User: missing key → false; namespace chosen by PSR-4 best practice (also closes the `apps/Config`/`apps/Services` autoload gap). |
| D7 | 2026-11-20 | **All test files live in `/tests`** (new tracked top-level dir); smoke checks never in `public/` or `src/`. Bootstrap smoke check: `tests/smoke_check.php` (self-contained, any CWD). | User directive; keeps the web root clean. Recorded per the no-new-top-directory rule. |
| D8 | 2026-11-20 | `app.mode` env toggle: `bone` (headless API backend only), `face` (SSR frontend → headless backend, server-to-server), `full` (both; default). Selectable before app development. Pair of FR-12 and Phase 4; `.env` carries the key with a docblock. | PRD §1 already defines the two selectable modes; this makes the selection an explicit env key instead of an implicit convention. |
| D9 | 2026-11-20 | Modular HMVC lives at **project root `modules/`** (sibling of `apps/`), set via `$modules_folder` in `public/index.php`. `apps/` is always the main MVC. `modulesf` path constant always registered. | User: modules are optional and must not pollute `apps/`; keeps `apps/` a clean, default MVC. Replaces the Phase 0b intermediate where the module tree lived in `apps/Modules/`. |
| D10 | 2026-11-20 | Two-root autoload: `App\…`→`apps/` and `Modules\…`→`modules/` **always active**. `app.modules` gates only the per-module folder/namespace templates (`controllersfmod` etc.), not root resolution. Gateway renamed `public/nanoradf.php` → `nanoradf`. | Audit of user refactor found mixed constant contracts (`nsappmod`/`appmodf` undefined) + `echo` leak; unified to two roots. Debug disclosure settled later (D11). |
| D11 | 2026-11-21 | One debug switch: `APP_DEBUG` derived from `app.debug` (in `.starter`) drives error disclosure and reporting; the entry-level `$debug` master is removed so config can't silently flip it off. Route table: flat `'GET /path'` + prefix groups (group key = prefix, inner keys full `METHOD /path`). | Two independent switches ($debug vs APP_DEBUG) contradicted each other; prefix spelling was redundant. |
| D12 | 2026-11-22 | View system: dot-notation (`file`, `folder.file`, `Module.file`, `Module.folder.file`); layout is third arg of `View::render()`; `{{ }}` auto-escape; AssetsService mask_url config in `apps/Config/AssetsConfig.php`. | Phase 2 kickoff. |
| D13 | 2026-11-22 | Leading capital = module → view folders lowercase (`apps/Views/layouts/main.html`). `src/Views` keeps capitalised taxonomy, reached via constants. | View taxonomy convention. |
| D14 | 2026-11-23 | Security hardening phase added: CSRF, Rate Limiting, Security Headers (CSP, HSTS, X-Frame-Options) as Phase 2b before Phase 3. | Review findings: critical security gaps. |
| D15 | 2026-11-23 | Code quality phase added: dead code cleanup, ThemeHelper implementation, orphan file removal as Phase 2c. | Review findings: dead code, empty ThemeHelper, orphan Theme.php. |
| D16 | 2026-11-23 | AssetsService simplification as Phase 4b after headless client. | Review finding: over-complex url() method. |

## Status legend

✅ done · 🔵 in progress · ⚠️ pending · ⛔ blocked

## Phases

### Phase 0 — Bootstrap & kernel ✅
- Entry chain `public/index.php` → `nanoradf` → `.config` → `.supports` → `.structures` → `Boot::initiate()`.
- Global helpers (`is`, `interlace`, `pathify`, `namespacify`, `urlify`, `genv`, `gserver`, `enval`).
- Path/namespace constants, `is_dir` guarded; PSR-4 autoloader.
- **2026-11-20 fixes (D3):** undeclared `self::$nssys`/`self::$nsmod` → global constants; undefined `modulesf` → resolved via the `Modules\…` branch. Fail-before/pass-after recorded in `docs/REPORT.md`.
- **D10 (2026-11-20, post-audit):** two-root autoload contract — `App\…`→`apps/`, `Modules\…`→`modules/` always active; `nsapp`/`nsmod` replace `nsappmod`/`appmodf`.
- **D9:** root-modules relocation (see Phase 0b) — `modulesf` replaces the old combined constant; `modulesfapp` removed.
- Verification: boot smoke check + autoload resolution of `System\Helper\Utilities` and `App\Modules\Welcome\Controllers\WelcomeController` → PASS (exit 0).

### Phase 0b — `app.modules` layout toggle ✅
- **Depends on:** Phase 0. **Owner:** main thread. **Decisions:** D6 + D9 + D10.
- Both app roots are always autoloaded: `App\…` → `apps/`, `Modules\…` → `modules/` (D10).
- `.config` registers `modulesf` from `$modules_folder` (root `modules/`) always; namespaces: `nsapp`=`App`, `nsmod`=`Modules`.
- `app.modules=true` registers the per-module folder/namespace templates under `modules/%s/{Config,Controllers,Model,Services,Views}` (`controllersfmod`, `nsctrlmod`, …) with the `Manifest.json` contract, `is_dir` guarded.
- `app.modules=false`/absent → templates unregistered; both roots still resolve.
- Current `.env` carries `app.modules=false` (working default); the key itself stays gitignored — semantics documented here and in PRD FR-11.
- Verification: `tests/layout_check.php` boots **both** ways asserting `nsapp`/`nsmod` + both roots resolve + template gate; `tests/smoke_check.php` exit 0; `http://nanoradfw.local` → 200. Fail-before/pass-after in `docs/REPORT.md`.

### Phase 1 — Routing & controllers ✅
- **Depends on:** Phase 0, Phase 0b (controller/model/view locations are defined there).
- **Owner:** main thread.
- Implement `src/Kernel/Router.php` (method + path matching, params, 404 fallthrough) and `src/Kernel/Controller.php` (request lifecycle, response helpers, render hook).
- Wire `apps/Config/RoutesConfig.php` as the route table consumed by the router.
- Base controllers `PublicController` / `PrivateController` / `ApiController` (surface-specific auth/response rules).
- Verification: CLI route-match checks (match/miss/param cases) + `http://nanoradfw.local` returns a controller response, unknown path → 404 handler.
- **2026-11-21:** Router (exact-first, `{name}`/`{name:regex}`, 405/404, normalise, prefix groups), Controller base + `authorize()`/`respond()`, three base controllers with distinct surfaces; gateway dispatches only on web SAPIs. `tests/route_check.php` covers match/miss/param/regex/normalise/group/malformed; smoke + layout stay green.
- **2026-11-22 (gateway relief):** dispatch moved to `src/Kernel/Dispatcher.php`; the gateway now has a **single catch-all** (runtime faults collapsed into it) instead of nested try/catch; bootstrap sentinels still report via `$error`; disclosure gated by `APP_DEBUG`. Fail-before/pass-after: before = runtime fail printed guts in two places, after = one renderer (detail only when APP_DEBUG).
- **2026-11-22 (finish pass):** api error envelope end to end (`/status/{code:[45]\d\d}` → `{"ok":false,"error":"…"}` JSON); disclosure unified on `is_debug()`; namespace experiment cancelled; route capture-group canonicalization (`9371800`).
- **2026-11-21 follow-up (format + priority batch):** route table keys are flat `'GET /path'` with prefix groups (`'/admin' => [ 'GET /dash' => ... ]`; group key = prefix, inner keys full `METHOD /path`); `Allow:` derives from the table; HEAD served from GET (empty body); API responses carry `application/json`. Browser-verified: `/`, `/hello/{name}`, `/ping`, `POST /` → 405.

### Phase 2 — View system & assets ✅
- **Depends on:** Phase 1. **Owner:** main thread.
- Blade-shaped renderer: compose `Layouts/` ← `Patterns/` ← `Components/`/`Elements/`, module `Views/*.php`, escaping helper in `src/Helper/Utilities.php`.
- Dot-notation view names (**no extension in the parameter**): `file` → own scope (in a module: its **own** view, requires `app.modules=true` else file-not-found); `folder.file`; cross-module only: `Module.file` / `Module.folder.file`. `.html` is applied by the View/Drip layer as a rule — never written manually. When a view cannot be found, the error must state the resolved location and tell the user to "pastikan file ada" and "pastikan menggunakan file html". Layout is the third argument of `View::render(...)` — no `@layout` in view files; `@extends/@yield/@section/@endsection/@include` inside templates.
- `{{$var}}` auto-escaped (spaces optional), `{{!$var!}}` raw opt-out. Manual escape functions inside `{{ }}` are rejected at compile time (double-escape guard). `{{-- … --}}` is dropped; a directive/echo wrapped in an HTML comment is unwrapped and compiled so templates stay previewable.
- `src/Services/AssetsService.php` + theme registration (`public/themes/default`, `src/Themes/admin`), design tokens from `src/Views/Tokens/System.css`; disk→URL mapping via mask_url configured in `apps/Config/AssetsConfig.php`.
- Verification: rendered page with layout+pattern composition; escaping test (malformed input stays inert HTML).
- **2026-11-22 kickoff decisions:** dot-notation (`file`, `folder.file`, `Module.file`, `Module.folder.file`); layout is the third arg of `View::render()` (no `@layout` in view files); `{{ }}` auto-escape; AssetsService mask_url config in `apps/Config/AssetsConfig.php`; PRD acceptance #8 added. Execution in small verified steps per `docs/RULES.md` §9.
- **2026-11-22 batch 1 delivered (D12):** `System\Helper\Utilities` (`escape`, `classAttr`), `System\Services\Views` (dot-notation → `apps/Views` only, lowercase folders; leading capital = module, gated by `app.modules`; module context derived from the controller FQCN; `.html` appended; not-found error names both causes), `System\Services\Drip` (compile-to-PHP cache in `storage/cache/views/`, mtime invalidation, echo/raw/comment/guard, `@extends/@section/@endsection/@yield/@include/@if/@elseif/@else/@endif/@foreach/@for/@php/@class`), `Controller::view()`, demo `/welcome`. `tests/view_check.php` → 16/16 pass; `route_check.php` + boot smoke unchanged.
- **Deferred (design-system directives):** `@slot/@endslot`, `@stack/@push/@prepend`, `@once`, `@atomic` — 0 production uses in the reference design system; add when the component partials need them.
- **Rule consequence (D13):** a leading capital always means a module, so view folders are lowercase (`apps/Views/layouts/main.html`). `src/Views` keeps its capitalised taxonomy and is reached through constants, not dot-notation.
- **2026-11-22 batch 3 delivered:** Pattern-based `mask_urls` with `{*}` regex capture and theme placeholders (`{face_theme_active_name}`, `{bone_theme_active_name}`); separate face/bone queues in `AssetsService`; helper auto-autoload via `apps/Config/HelperConfig.php` stem registry loaded by `.starter`; `Views::render()` auto-injects `head`/`foot`/`bone_head`/`bone_foot` from `AssetsService`; Drip directives `@enqueueStyles`/`@enqueueScripts`/`@style`/`@script` added; asset interception moved to `Dispatcher::dispatchAssets()` (before Router dispatch, no route entries); error pages from `src/Views/Errors/` rendered via Drip without layout wrapping; router throws on 404/405, dispatcher catches and delegates to `errorPage()`; CSS token design system collapsed into single `System.css`; theme folder renamed admin→default with `src/Config/ThemeConfig.php`; config/theme helpers added. `tests/assets_check.php` → 24/24 pass; `view_check.php` → 16/16; `route_check.php` PASS. Browser verified: `/notfound` → themed 404 page, `/welcome` → full SSR page with auto-injected assets.
- **2026-11-23 Phase 2b:** Security hardening complete — CSP, HSTS, X-Frame-Options, X-Content-Type-Options, Referrer-Policy added via `Dispatcher::securityHeaders()` in `emit()`.
- **2026-11-23 Phase 2c:** Code quality complete — dead code removed (`candidate`, `diskPath`, `rewrite`), `ThemeHelper` implemented, ThemeConfig separated from AssetsConfig, `AssetsService::theme()` updated to read from ThemeConfig with backwards-compat aliases.
- **2026-11-23 Phase 4b:** AssetsService simplification complete — `url()` and `resolve()` rewritten with segment prefix matching replacing per-request PCRE regex; pre-compiled pattern map cached; `anchor()` helper added for config disk path resolution.

### Phase 2b — Security hardening ✅
- **Depends on:** Phase 2. **Owner:** main thread. **Priority:** P0 (Critical).
- **CSRF Protection:** Implement CSRF token generation/validation for all state-changing endpoints (POST/PUT/PATCH/DELETE). Token stored in `$_SESSION`, validated in `Router::run()` or `Controller::respond()` before action execution.
- **Rate Limiting:** Implement token bucket / sliding window rate limiter in `Dispatcher::handle()` for auth endpoints (login, token refresh) and global fallback.
- **Security Headers:** Add CSP, HSTS, X-Frame-Options, X-Content-Type-Options, Referrer-Policy in `Dispatcher::emit()`.
- **Verification:** Security headers present in all responses (verified via manual HTTP check).
- **2026-11-23:** Security headers implemented in Phase 2b. CSRF and Rate Limiting deferred to future phase.

### Phase 2c — Code quality & cleanup ✅
- **Depends on:** Phase 2. **Owner:** main thread. **Priority:** P1 (High).
- **Dead Code Removal:** Delete `AssetsService::candidate()`, `AssetsService::diskPath()`, `AssetsService::rewrite()` (unused).
- **ThemeHelper Implementation:** Implement `activeTheme()` in `src/Helper/ThemeHelper.php` (currently empty but registered).
- **Orphan File Removal:** Delete `src/Config/Theme.php` (orphan, not tracked by git).
- **Theme Config Separation:** Move theme config from `AssetsConfig.php` to dedicated `apps/Config/ThemeConfig.php` (aligns with `gconf()` pattern).
- **ThemeHelper Registry:** Ensure `'theme'` stem in `HelperConfig.php` works with implemented `ThemeHelper.php`.
- **Theme Resolver Hook (Phase 5 prep):** Add `ThemeResolverInterface` to `src/Contracts/Interfaces/` and register `resolver` key in `ThemeConfig` (default `null`). Document the seam for database-driven theme override.
- **Verification:** All tests pass; no dead code; ThemeHelper functional; config structure consistent.
- **2026-11-23:** All items completed. Dead code removed, ThemeHelper implemented, ThemeConfig separated, AssetsService::theme() updated.
- **2026-11-24 Phase 2c follow-up:** Surface resolution moved to class hierarchy (`SurfaceAwareInterface` + `SurfaceResolverTrait`, no mutable `$surface`); layouts moved to active theme only (`<theme>/index.html`, no fallback); error pages standalone; `apps/Views/layouts/` removed; theme layouts created for `public/themes/default`, `apps/Themes/default`, `apps/Themes/admin`.

### Phase 2d — Asset register restructuring ⚠️
- **Depends on:** Phase 2c. **Owner:** main thread. **Priority:** P1 (High). Breaking change.
- Replace flat `id_registry` array with two explicit arrays: `register_styles` and `register_scripts`.
- New format per entry: `['id' => <string>, 'path' => '<mask-relative-path-with-extension>']`. Optional `scope` key defaults to both face and bone.
- When `id` is omitted, derive default id from basename (e.g. `/css/style.css` → `style`).
- Path extension must be present in config — remove auto-extension logic from `AssetsService::names()`.
- Render: build `$pathToId` lookup table directly from registry entries (eliminate reverse-loop lookup).
- Enqueue lists (`enqueue_styles`, `bone_enqueue_styles`, etc.) keep current string-keyed form — values are now full masked paths, not symbolic ids.
- Fix long-standing `array_replace_recursive` empty-array regresi (REPORT A1/A2/A3): enqueue lists properly layered over framework defaults.
- Files: `src/Config/AssetsConfig.php`, `apps/Config/AssetsConfig.php`, `src/Services/AssetsService.php`, `tests/assets_check.php`.

### Phase 3 — Welcome module end-to-end ⚠️
- **Depends on:** Phase 2, 2b, 2c. **Owner:** main thread.
- Fill `modules/Welcome/` (Manifest, Config, Controller, Model, Service, Views) and register its route.
  - `modules/Welcome/Manifest.json` — identity, enabled flag, registration metadata.
  - `modules/Welcome/Config/WelcomeConfig.php` — module config (mergeable via `gconfig()`).
  - `modules/Welcome/Controllers/WelcomeController.php` — extends `PublicController`, actions for `/welcome`.
  - `modules/Welcome/Model/WelcomeModel.php` — data access (read-only vs headless API in Phase 4).
  - `modules/Welcome/Services/WelcomeService.php` — business logic.
  - `modules/Welcome/Views/welcome.index.html` — Drip template using theme layout.
- Register route in `apps/Config/RoutesConfig.php`: `'GET /welcome' => 'Modules\Welcome\Controllers\WelcomeController::index'`.
- Verification: `/welcome` over HTTP renders through the view composer with theme layout; removing the module route yields 404; module context resolution works (`ViewsService::context()` returns `Welcome`).

### Phase 4 — Headless mode & server-to-server client ⚠️
- **Depends on:** Phase 1 (parallel-safe with Phase 2–3). **Owner:** main thread.
- `ApiController` conventions: JSON envelope `{ok, data}` / `{ok:false, error}`, proper HTTP status codes, consistent error shape.
- Backend route group under `/api` prefix (e.g., `/api/v1/users`, `/api/v1/posts`).
- Frontend→backend PHP HTTP client (`System\Services\HttpClient` or similar):
  - cURL primary, stream context fallback.
  - Timeout, retry, circuit breaker config via `gconfig('http_client')`.
  - Validate HTTP status (2xx only) + JSON schema (required keys, types) before rendering.
  - No browser `fetch` for data — all SSR data fetched server-side.
- Base URL for backend from `gconfig('app.backend_url')` or `genv('app.backend_url')`.
- Verification: SSR page renders data fetched server-side; grep confirms no `fetch(`/`XMLHttpRequest` in public JS; client only receives final HTML.

### Phase 4b — AssetsService refactor (SRP) ⚠️
- **Depends on:** Phase 4. **Owner:** main thread. **Priority:** P2 (Medium).
- Current `AssetsService` (~21 KB, 600+ lines) does too much: config, enqueue, URL masking, resolution, rendering, serving, pattern caching, versioning, MIME lookup.
- Split into focused classes:
  - `AssetConfig` — read/merge config (`gconfig('assets')`), expose `id_registry`, `enqueue_*`, `mask_urls`.
  - `AssetResolver` — `resolve(string $url): ?array{file,mime}` using segment prefix matching + cached pattern map; `anchor()` for disk path.
  - `AssetUrlGenerator` — `url(string $name, string $surface): string` with cache-buster; uses `AssetResolver` internally.
  - `AssetRenderer` — `renderFaceStyles()`, `renderBoneScripts()`, etc.; builds `<link>`/`<script>` tags with `id` from registry.
  - `AssetServer` — `serveAsset(string $file, bool $head)` — MIME, headers, streaming (used by `Dispatcher::dispatchAssets()`).
- `AssetsService` becomes a thin facade delegating to the above; backward-compatible static methods preserved.
- Remove legacy `ds/` fast-path in `url()`.
- Verification: All asset tests pass; each class < 150 lines; single responsibility clear; no circular deps.

### Phase 5 — Session & auth token storage ⚠️
- **Depends on:** Phase 4. **Owner:** main thread.
- `$_SESSION`-only access/refresh token storage; cookie flags `HttpOnly`/`SameSite=Strict`/`secure` (prod); CSRF nonce for state-changing routes; auth guards on `PrivateController`/`ApiController`.
- **Theme resolver implementation:** `DatabaseThemeResolver` implementing `ThemeResolverInterface` — reads active theme per surface from DB (user preference, tenant config). Register class name in `ThemeConfig['resolver']`. `theme()` helper calls resolver first, falls back to config `default` map.
- Verification: token never appears in client storage (grep); unauthenticated private route rejected; nonce check blocks forged POST; theme resolver overrides config when registered.

### Phase 6 — Errors, polish, docs sync ⚠️
- **Depends on:** Phase 3, 4. **Owner:** main thread.
- 404/500 pages from `src/Views/Errors/`, `.env` defaults documented, README final pass, docs sync review.
- **Theme folder naming consistency:** Unify `public/themes/` (lowercase) and `apps/Themes/` (capital) — pick one convention, update `mask_urls` patterns, `AssetsService::anchor()`, `ViewsService::resolveThemeLayout()`, and all references. This is a breaking change; do with version bump.
- Verification: 404/500 render themed pages; docs cross-reference check (no dangling file refs); theme folder naming consistent across codebase.

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
| 4 | 2 | Phase 1 done |
| 5 | 2b, 2c | Phase 2 done (parallel: security + cleanup) |
| 6 | 2d, 3 | Phase 2d asset register restructuring + Phase 3 Welcome module |
| 7 | 4b | Phase 4 done |
| 8 | 5 | Phase 4b done |
| 9 | 6 | Phases 3 + 4 done |
| 10 | 7 | everything above done, user invokes `>audit`/`>release` |

## Track log

| Date | Result | Evidence |
|------|--------|----------|
| 2026-11-20 15:11 | **Plan == code, no drift.** Phase 0 re-verified (boot smoke + autoload: `BOOT-LOADED`, `autoload sys=YES mod=YES`, exit 0). Phases 1–6: every target file still a 0-byte scaffold (`Kernel/Router.php`, `Kernel/Controller.php`, Api/Private/Public controllers, `apps/Config/RoutesConfig.php`, `Helper/UtilitiesHelper.php`, `AssetsService.php`, all `src/Views/*`, Welcome module, themes) → ⚠️ statuses correct, no phase advanced. Open risks #1–#3 unchanged. Working tree clean at `24404fa`; no `origin` remote → push skipped. | `git status`, per-file size inventory, `boot_check.php` |
| 2026-11-23 15:30 | **Phase 0–2 complete.** Bootstrap, routing, view system, assets all functional. All test suites PASS (smoke, assets, view, route). Security hardening (Phase 2b), code quality (Phase 2c), and AssetsService simplification (Phase 4b) added to plan based on review findings. | `tests/*.php` all PASS; `git status` clean; PR #3 MERGED |
| 2026-11-24 22:40 | **Phase 2c advanced: surface resolution + theme layouts.** `SurfaceAwareInterface` + `SurfaceResolverTrait` determine surface from class hierarchy (no mutable `$surface`). Layouts moved to active theme only (`<theme>/index.html`), no fallback. Error pages standalone. Theme resolver hook prep for Phase 5 (`ThemeResolverInterface`, `resolver` key in config). Bug fix: restored `apps/Config/ThemeConfig.php` `'private' => 'admin'` mapping accidentally changed during resolver work. | 8/8 tests PASS; commit `e8a9990` + `3cf5eb4` + `6e25ed7` |

## Pending improvements

| ID | Issue | Severity | Status | File ref |
|----|-------|----------|--------|----------|
| P1 | `allPatterns()` / `patternMap()` return patterns in config-file order — ambiguous when prefixes overlap (`/css/{*}` vs `/system/css/{*}`). Sort descending by prefix length before matching to guarantee longest-prefix-wins. | Medium | ⚠️ pending | AssetsService.php:226,355 |
| P2 | Dead methods `regex()`, `matchPattern()`, `expand()` remain in AssetsService but are never called. Remove ~50 lines. | Low | ⚠️ pending | AssetsService.php:420–510 |

## Open risks

- `apps/Services` / `apps/Config` classes have no namespace mapping today — closed **by design** in Phase 0b (single `App\` → `apps/` root); confirm with the both-mode verification there.
- `read_directories()` removed in Phase 1 (no caller; entry no longer declares `$directories`).
- No test framework: smoke checks are manual until a test runner is agreed (candidate: plain-PHP assert scripts, zero deps).
- **Phase 2b:** Security headers implemented. CSRF and Rate Limiting deferred to Phase 5.
- **Phase 2c:** Code quality cleanup completed. Theme resolver hook added for Phase 5.
- **Phase 4b:** AssetsService refactor (SRP) pending — current monolithic class needs split into `AssetConfig`, `AssetResolver`, `AssetUrlGenerator`, `AssetRenderer`, `AssetServer`.
- **Theme folder naming:** `public/themes/` (lowercase) vs `apps/Themes/` (capital) — inconsistent. Phase 6 will unify (breaking change).
- **Module HMVC:** `modules/Welcome/` still 0-byte scaffold. Phase 3 will fill it.
