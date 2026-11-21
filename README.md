# NanoRADF

Minimal PHP framework with **two selectable modes**: a server-side rendered **frontend** (PHP + HTML + CSS + JS) and a **headless backend** (PHP + API) — communicating **server-to-server**, never via browser fetch. Pick a mode before you start: frontend, headless, or full package (both).

- **PHP 8.1+**, `strict_types` everywhere, **zero runtime dependencies** (custom PSR-4 autoloader, no Composer at runtime).
- **SSR first**: PHP renders HTML; vanilla JS is progressive enhancement only.
- **Token auth** lives server-side in `$_SESSION` — never in `localStorage`.

## Status

Scaffold phase. The bootstrap chain (entry → gateway → helpers → structures → kernel autoloader) runs; router, view composer, and modules are placeholders. Plan: [docs/PLAN.md](docs/PLAN.md). Requirements: [docs/PRD.md](docs/PRD.md).

## Requirements

- PHP 8.1+ (developed on 8.3.36 via LocalWP)
- Apache/nginx with URL rewriting, **docroot = `public/`**
- Dev URL: **http://nanoradfw.local**

## Request flow

```text
public/index.php          entry: folder names ($application_folder, $modules_folder, $system_folder), $debug
  └─ public/nanoradf   gateway: basepath/publicpath/sep, error gate
       └─ .config         loads .supports (helpers) + .structures (paths/namespaces)
            └─ src/Kernel/Boot.php   initiate() + PSR-4 autoloader
                 └─ Router → Controller → View     (planned, Phase 1–2)
```

## Layout

```text
public/   web root — only entry point (.htaccess front controller, themes/)
src/      framework core (System\...) — Kernel, base controllers, services, views taxonomy
apps/     application layer (App\...) — Config/Routes.php, Services/, Controllers/, Model/, Views/
modules/  optional HMVC (Modules\...) — <Name>/ with Manifest.json + Config/Controllers/Model/Services/Views
docs/     RULES · PLAN · PRD · REPORT · CHANGELOG
```

Autoload map (two roots, always active): `System\...` → `src/...`, `App\...` → `apps/...`, `Modules\...` → `modules/...`. `app.modules=true` registers the per-module folder/namespace templates under `modules/<Name>/`; `false`/absent leaves both roots resolving but templates unregistered.

## Getting started

1. Point the LocalWP site docroot at `public/` → open http://nanoradfw.local.
2. Copy `.env` values as needed (gitignored; `app.env=local` by default; debug disclosure via `APP_DEBUG`, derived from `app.debug`).
3. Read [docs/RULES.md](docs/RULES.md) before changing code — especially the boot smoke check for the bootstrap dotfiles.

## License

MIT — see [LICENSE](LICENSE).

