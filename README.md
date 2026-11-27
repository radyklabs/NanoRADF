# NanoRADF

![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-8892BF?logo=php&logoColor=white)
![Version](https://img.shields.io/badge/Version-0.0.1-blue)
![Phase](https://img.shields.io/badge/Phase-5%20Complete-brightgreen)
![License](https://img.shields.io/badge/License-MIT-blue)
![Tests](https://img.shields.io/badge/Tests-Passing-brightgreen)
![Dependencies](https://img.shields.io/badge/Dependencies-Zero-orange)

Minimal PHP framework with **two selectable modes**: a server-side rendered **frontend** (PHP + HTML + CSS + JS) and a **headless backend** (PHP + API) — communicating **server-to-server**, never via browser fetch. Pick a mode before you start: frontend, headless, or full package (both).

- **PHP 8.1+**, `strict_types` everywhere, **zero runtime dependencies** (custom PSR-4 autoloader, no Composer at runtime).
- **SSR first**: PHP renders HTML; vanilla JS is progressive enhancement only.
- **Token auth** lives server-side in `$_SESSION` — never in `localStorage`.

## Status

**Phase 5 Complete.** Bootstrap, routing, view system (Drip), assets, security headers, sentinelized error handling, headless API mode, server-to-server HTTP client, AssetsService internal refactor, session/auth token storage, CSRF protection, and auth guards all functional. All test suites PASS (smoke, assets 24/24, view 14/14, route 15/15, layout, auth 17/17).

- **Completed Phases:** 0, 1, 2, 2b, 2c, 2d, 3 (Welcome module), 4 (Headless mode), 4b (AssetsService internal refactor)
- **Pending Phases:** 6 (Polish/docs/theme-resolver), 7 (Audit/release)
- **Deferred:** Blade-shaped composer (`Layouts` ← `Patterns` ← `Components`/`Elements`), registry `scope` key for per-surface filtering

## Requirements

- PHP 8.1+ (developed on 8.3.36 via LocalWP)
- Apache/nginx with URL rewriting, **docroot = `public/`**
- Dev URL: **http://localhost** (or your configured vhost)

## Implemented Features (Phases 0–5 Complete)

- **Router** — PSR-4 autoloaded, module registry via `ModulesConfig.php`, API routes gated by `app.mode` (face/bone/full)
- **Drip template engine** — `src/Services/DripService.php`, file-based rendering with data injection
- **AssetsService** — Single class with internal sections (Config, Pattern Cache, Resolution, URL Generation, Rendering, Queue Management, Helpers, Serving); `mask_urls` config with `{*}` wildcards and theme placeholders; segment prefix matching (no per-request regex)
- **Security headers** — X-Content-Type-Options, X-Frame-Options, Referrer-Policy always; HSTS on HTTPS; CSP differentiated for JSON vs HTML
- **Sentinelized error handling** — 404/405/500 render themed error pages via Drip; debug detail only when `is_debug()` true
- **Theme-based layouts** — `ThemeConfig.php` with `default.public`/`default.private`; `face`/`bone` aliases; `ThemeHelper::resolve()`
- **Headless API mode** — `app.mode=bone|full` enables `/api` prefix group; `ApiController` with `json()`, `ok()`, `error()`, `notFound()`, `unauthorized()`, `forbidden()`, `validationError()`, `serverError()` helpers
- **Server-to-server HTTP client** — `HttpService` (cURL primary, stream fallback); exponential backoff retry; JSON validation; configurable via `gconfig('http')`
- **AssetsService internal refactor** — `serveAsset()` with conditional GET (ETag + If-Modified-Since → 304); `Dispatcher::dispatchAssets()` delegates to `AssetsService::serveAsset()`
- **Session & auth storage** — `Session` (lazy writer-starts-session), `AuthService` (token storage only, no refresh logic), `AuthConfig` (fail-closed API validator)
- **CSRF protection** — `CSRFService` per-session token, `hash_equals` validation, `regenerate()` hook for module login
- **Auth guards** — `PrivateController::authorize()` (session-based), `ApiController::authorize()` (Bearer + configurable validator); Router handles 403/401

## Layout

```text
public/   web root — only entry point (.htaccess front controller, themes/)
src/      framework core (System\...) — Kernel, base controllers, services, views taxonomy
apps/     application layer (App\...) — Config/RoutesConfig.php, Config/AssetsConfig.php, Services/, Controllers/, Model/, Views/
modules/  optional HMVC (Modules\...) — <Name>/ with Manifest.json + Config/Controllers/Model/Services/Views
docs/     RULES · PLAN · PRD · REPORT · CHANGELOG
```

Autoload map (two roots, always active): `System\...` → `src/...`, `App\...` → `apps/...`, `Modules\...` → `modules/...`. `app.modules=true` registers the per-module folder/namespace templates under `modules/<Name>/`; `false`/absent leaves both roots resolving but templates unregistered.

## Getting Started

**Quick start for local development:**

1. Point your web server docroot at `public/` → open **http://localhost** (or your configured vhost).
2. Duplicate `.env.sample` to `.env` and adjust values as needed (gitignored; `app.env=local` by default; debug disclosure via `is_debug()`, driven by `app.debug`).
3. Read [docs/RULES.md](docs/RULES.md) before changing code — especially the boot smoke check for the bootstrap dotfiles.

## License

MIT — see [LICENSE](LICENSE).

