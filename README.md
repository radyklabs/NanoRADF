# NanoRADF

![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-8892BF?logo=php&logoColor=white)
![Phase](https://img.shields.io/badge/Phase-2%20Complete-brightgreen)
![License](https://img.shields.io/badge/License-MIT-blue)
![Tests](https://img.shields.io/badge/Tests-Passing-brightgreen)
![Dependencies](https://img.shields.io/badge/Dependencies-Zero-orange)

Minimal PHP framework with **two selectable modes**: a server-side rendered **frontend** (PHP + HTML + CSS + JS) and a **headless backend** (PHP + API) — communicating **server-to-server**, never via browser fetch. Pick a mode before you start: frontend, headless, or full package (both).

- **PHP 8.1+**, `strict_types` everywhere, **zero runtime dependencies** (custom PSR-4 autoloader, no Composer at runtime).
- **SSR first**: PHP renders HTML; vanilla JS is progressive enhancement only.
- **Token auth** lives server-side in `$_SESSION` — never in `localStorage`.

## Status

**Phase 2 Complete.** Bootstrap, routing, view system (Drip), assets, security headers, and sentinelized error handling all functional. All test suites PASS (smoke, assets 24/24, view 14/14, route 15/15, layout).

- **Completed Phases:** 0, 1, 2, 2b, 2c, 2d
- **Pending Phases:** 3 (Welcome module), 4 (Headless mode), 4b (AssetsService SRP refactor), 5 (Auth/session), 6 (Polish/docs), 7 (Audit/release)
- **Deferred:** Blade-shaped composer (`Layouts` ← `Patterns` ← `Components`/`Elements`), registry `scope` key for per-surface filtering

## Requirements

- PHP 8.1+ (developed on 8.3.36 via LocalWP)
- Apache/nginx with URL rewriting, **docroot = `public/`**

## Request flow

```text
public/index.php          entry: folder names ($application_folder, $modules_folder, $system_folder), $debug
  └─ public/nanoradf   gateway: basepath/publicpath/sep, error gate
       └─ .config         loads .supports (helpers) + .structures (paths/namespaces)
            └─ src/Kernel/Boot.php   initiate() + PSR-4 autoloader
                 └─ Router → Controller → View     (Phase 1–2 complete)
```

**Currently working:** Router, Drip template engine, AssetsService (mask_url), security headers (CSP/HSTS/X-Frame), sentinelized error handling, theme-based layouts.

## Layout

```text
public/   web root — only entry point (.htaccess front controller, themes/)
src/      framework core (System\...) — Kernel, base controllers, services, views taxonomy
apps/     application layer (App\...) — Config/RoutesConfig.php, Config/AssetsConfig.php, Services/, Controllers/, Model/, Views/
modules/  optional HMVC (Modules\...) — <Name>/ with Manifest.json + Config/Controllers/Model/Services/Views
docs/     RULES · PLAN · PRD · REPORT · CHANGELOG
```

Autoload map (two roots, always active): `System\...` → `src/...`, `App\...` → `apps/...`, `Modules\...` → `modules/...`. `app.modules=true` registers the per-module folder/namespace templates under `modules/<Name>/`; `false`/absent leaves both roots resolving but templates unregistered.

## Getting started

1. Point the LocalWP site docroot at `public/` → open http://nanoradfw.local.
2. Copy `.env` values as needed (gitignored; `app.env=local` by default; debug disclosure via `is_debug()`, driven by `app.debug`).
3. Read [docs/RULES.md](docs/RULES.md) before changing code — especially the boot smoke check for the bootstrap dotfiles.

## License

MIT — see [LICENSE](LICENSE).

