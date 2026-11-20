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

## Status legend

✅ done · 🔵 in progress · ⚠️ pending · ⛔ blocked

## Phases

### Phase 0 — Bootstrap & kernel ✅
- Entry chain `public/index.php` → `nanoradf.php` → `.config` → `.supports` → `.structures` → `Boot::initiate()`.
- Global helpers (`is`, `interlace`, `pathify`, `namespacify`, `urlify`, `__env`, `__server`, `cast_value`, `env_clear_cache`, `read_directories`).
- Path/namespace constants, `is_dir` guarded; PSR-4 autoloader.
- **2026-11-20 fixes (D3):** undeclared `self::$nssys`/`self::$nsmod` → global constants `nssys`/`nsappmod`; undefined `modulesf` → `modulesfapp`. Fail-before/pass-after recorded in `docs/REPORT.md`.
- Verification: boot smoke check + autoload resolution of `System\Helper\Utilities` and `App\Modules\Welcome\Controllers\WelcomeController` → PASS (exit 0).

### Phase 1 — Routing & controllers ⚠️
- **Depends on:** Phase 0. **Owner:** main thread.
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
| 2 | 1 | Phase 0 done |
| 3 | 2, 4 | Phase 1 done (2 and 4 are independent) |
| 4 | 3, 5 | Phase 2 / Phase 4 done |
| 5 | 6 | Phases 3 + 4 done |
| 6 | 7 | everything above done, user invokes `>audit`/`>release` |

## Open risks

- Autoloader maps only `System\` and `App\Modules\` — shared `apps/Services/` classes have no namespace mapping yet (resolve in Phase 1; record decision when routing wires them).
- `read_directories()` is currently uncalled (entry no longer declares `$directories`) — decide keep-or-remove in Phase 1, record in REPORT/CHANGELOG.
- No test framework: smoke checks are manual until a test runner is agreed (candidate: plain-PHP assert scripts, zero deps).
