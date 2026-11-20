# Changelog

All notable changes to NanoRADF are documented here.
Format: [Keep a Changelog](https://keepachangelog.com/en/1.1.0/). Versioning: [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added

- Project bootstrap chain: `public/index.php` → `public/nanoradf.php` → `.config` → `.supports` → `.structures` → `src/Kernel/Boot.php` with `initiate()` guard and gated error output.
- Global helper library in `.supports` (`is`, `urlify`, `namespacify`, `pathify`, `interlace`, `read_directories`, `__server`, `cast_value`, `__env`, `env_clear_cache`).
- Directory/namespace structure map (`.structures`, `is_dir` guarded) and PSR-4 autoloader for `System\...` and `App\Modules\...`.
- Application scaffold: `apps/` (Routes, Welcome module, shared Services) and `src/` core placeholders (Kernel Router/Controller, base controllers, Services, Helper, Contracts, Views taxonomy, themes).
- Web entry hardening: `.htaccess` front controller, `app.env`/`app.debug` gated error disclosure with `error_log` fallback.
- `.env` environment template (RADYK-style dot-notation keys).
- Project documentation: completed `CLAUDE.md` (Dev URL, Modular Architecture, Blade-shaped View System, Directory Layout, File Boundary, Secure Coding Rules) and new `docs/RULES.md`, `docs/PLAN.md`, `docs/PRD.md`, `docs/REPORT.md`, `docs/CHANGELOG.md`; expanded `README.md`.
- `docs/PLAN.md` track log: plan-vs-code status verification records (`>track` runs).
- PRD/PLAN coverage for the `app.modules` env toggle: flat vs modular `apps/` layout, default `false` when key missing, everything except `Manifest.json` moves flat, single `App\` PSR-4 root (PRD FR-11, PLAN Phase 0b, decision D6).
- `.gitignore` for local-only files (`.env`, `.envrc`, `.vscode/`, `sql/local.sql`, `docs/CONFIG.md`, `/vendor/`).

### Fixed

- `System\Kernel\Boot::autoloader` read undeclared static properties `self::$nssys` / `self::$nsmod` — first class load threw `Error: Access to undeclared static property`. Now reads the global namespace constants `nssys` / `nsappmod` registered by `.config` / `.structures`, with `defined()` guards and leading-backslash tolerance (fail-before/pass-after recorded in `docs/REPORT.md`).
- `System\Kernel\Boot::autoloader` resolved module paths from undefined constant `modulesf`; the constant defined by `.structures` is `modulesfapp`. First module class load threw `Error: Undefined constant`. Now resolves via `constant ('modulesfapp')`.
