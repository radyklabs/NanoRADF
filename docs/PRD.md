# PRD — NanoRADF

Status: v1 draft. Last reviewed: 2026-11-20.

## 1. Product summary

NanoRADF is a minimal PHP framework that runs in **two selectable modes** and can combine them:

1. **Frontend mode** — server-side rendered PHP app (PHP + HTML + CSS + JS), talking to a backend over HTTP.
2. **Headless mode** — headless PHP backend exposing an API for other frontends.

**Full package** = both in one codebase, communicating **server-to-server** (PHP HTTP client), never via browser fetch. The developer chooses the mode before starting an application.

Design mandate: minimal, small, light, fast. Zero runtime dependencies.

## 2. Users

| Persona | Need |
|---|---|
| Framework developer | Predictable bootstrap, clear module contract, no hidden magic, fast local iteration. |
| App developer (consumer) | Pick a mode, drop in modules/routes/views, ship an SSR app or API quickly. |
| End user | Fast, accessible pages; no JS required for core navigation. |

## 3. Functional requirements

| ID | Requirement | Status |
|----|-------------|--------|
| FR-1 | Boot chain: entry → gateway → helpers → structures → kernel autoloader, working on PHP 8.1+. | ✅ done |
| FR-2 | PSR-4 autoload for `System\...` → `src/`, `App\...` → `apps/`, and `Modules\...` → `modules/` (two app roots, always active). | ✅ done |
| FR-3 | Central route table (`apps/Config/Routes.php`) dispatched by a router to controllers. | ⚠️ planned (Phase 1) |
| FR-4 | Base controllers for the three surfaces: `PublicController`, `PrivateController`, `ApiController`. | ⚠️ planned (Phase 1) |
| FR-5 | Blade-shaped server-side view composition from the role taxonomy + module view classes. | ⚠️ planned (Phase 2) |
| FR-6 | Modular HMVC — only when `app.modules=true`: `Manifest.json` + Config/Controllers/Model/Services/Views under `modules/<Name>/` (project root), wired into routing. | ⚠️ planned (Phase 3) |
| FR-7 | Server-to-server HTTP client frontend → headless backend with status + schema validation. | ⚠️ planned (Phase 4) |
| FR-8 | Session-based auth token storage (`$_SESSION` only) with hardened cookie flags. | ⚠️ planned (Phase 5) |
| FR-9 | Environment config via `.env` loaded into the process env by `.starter` (`putenv` + `$_ENV`), dot-notation typed access via `genv()` (getenv-backed). | ✅ done |
| FR-10 | Error handling: generic 500 in prod, gated detail in local/dev, always `error_log`. | ✅ done (entry gateway) |
| FR-11 | **Layout mode toggle** via env `app.modules`: both roots (`App\...` → `apps/`, `Modules\...` → `modules/`) are always autoloaded. `app.modules=true` registers the per-module folder/namespace templates (`modules/<Name>/{Config,Controllers,Model,Services,Views}`, `Manifest.json`); `false`/absent leaves them unregistered (both roots still resolve). Namespace: single PSR-4 roots `App\` → `apps/` and `Modules\` → `modules/` (decision D6/D9). | ✅ done (Phase 0b) |
| FR-12 | **Runtime mode toggle** via env `app.mode`: `bone` (headless backend only — API surfaces, no SSR frontend), `face` (SSR frontend talking to a headless backend over HTTP server-to-server), `full` (both surfaces in one codebase; default). Chosen by the developer before building the app; gates which surfaces the framework wires. Values documented in `.env`; key stays gitignored. | ⚠️ planned (Phase 4) |
| FR-13 | **Autoload roots** (D6/D9, Phase 0b): `apps/` (main MVC) and `modules/` (optional HMVC) are both registered as PSR-4 roots (`App\` / `Modules\`). `app.modules` gates only the per-module folder/namespace templates (`configfmod`, `controllersfmod`, `modelfmod`, `servicesfmod`, `viewsfmod`). `modulesf` always registered in `.config` from `$modules_folder`. | ✅ done (Phase 0b) |

## 4. Non-functional requirements

- **NFR-1 Zero runtime dependencies** — no Composer packages at runtime.
- **NFR-2 PHP 8.1+**, `strict_types` everywhere; developed against PHP 8.3.36 (LocalWP).
- **NFR-3 SSR first** — core navigation works without JavaScript; JS is progressive enhancement only.
- **NFR-4 Security** — escape all output, prepared statements for any DB write, no `eval`/shell family on input, secrets only in `.env`, tokens only in `$_SESSION`.
- **NFR-5 Portability** — Windows + Linux path handling via `pathify`/`interlace`; Apache/nginx front controller.
- **NFR-6 Speed** — small bootstrap footprint; no per-request filesystem churn beyond the required includes.

## 5. v1 scope

**In:** `app.modules` layout toggle (flat default when key missing), working router + base controllers, view composer, Welcome module end-to-end, headless API mode + server-to-server client, session auth storage, 404/500 pages, docs set, smoke checks.

**Out of scope for v1:** browser-side fetch to backend, ORM/migrations, caching drivers, mail transport, queue, admin UI builder, third-party package integrations.

## 6. Acceptance criteria (v1)

1. `http://nanoradfw.local` serves an SSR page with zero JS errors; 404 renders `src/Views/Errors/404.html`.
2. A new module can be added by copying the Welcome folder contract and registering one route — no core edits.
3. Frontend fetches backend data via server-side PHP HTTP call only (grep shows no `fetch(`/`XMLHttpRequest` for data loading).
4. Boot smoke check passes after any bootstrap edit (see `docs/RULES.md` §3).
5. `grep -r` finds no `eval(`/`exec(`/`shell_exec(`/`system(`/`unserialize(` on input paths.
6. Tokens exist only in `$_SESSION` — no `localStorage` token access anywhere.
7. Both roots autoload: `App\...` → `apps/`, `Modules\...` → `modules/`. `app.modules=false` (or absent): per-module folder/namespace templates are not registered (`controllersfmod` UNDEF) but both roots still resolve. `app.modules=true`: templates registered (`controllersfmod` set).
