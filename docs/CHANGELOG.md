# Changelog

All notable changes to NanoRADF are documented here.
Format: [Keep a Changelog](https://keepachangelog.com/en/1.1.0/). Versioning: [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added
- Two-root autoload contract (D10, Phase 0b): `App\…` → `apps/` and `Modules\…` → `modules/` always active; namespaces `nssys`/`nsapp`/`nsmod`; `app.modules` gates the per-module templates (`configfmod`, `controllersfmod`, `modelfmod`, `servicesfmod`, `viewsfmod`). `tests/layout_check.php` asserts both roots resolve + template gate.

### Fixed
- Audit of the refactored bootstrap (`index.php`, `nanoradf`, `.config`, `.structures`, `Boot.php`): removed a fatal `Undefined constant "nsappmod"` in the autoloader/tests (`nsappmod`/`appmodf` were never defined), removed the `echo` debug leak in `.structures`, corrected modular namespace to `Modules\`, made `app.modules` functional again, and gated error disclosure with `$debug` in `index.php` (was hardcoded true, breaking FR-10).

### Added

- Project bootstrap chain: `public/index.php` → `public/nanoradf` → `.config` → `.supports` → `.structures` → `src/Kernel/Boot.php` with `initiate()` guard and gated error output.
- Global helper library in `.supports` (`is`, `urlify`, `namespacify`, `pathify`, `interlace`, `read_directories`, `__server`, `cast_value`, `__env`, `env_clear_cache`).
- Directory/namespace structure map (`.structures`, `is_dir` guarded) and PSR-4 autoloader for `System\...` and `App\...` / `App\Modules\...`.
- Application scaffold: `apps/` (Routes, Welcome module, shared Services) and `src/` core placeholders (Kernel Router/Controller, base controllers, Services, Helper, Contracts, Views taxonomy, themes).
- Web entry hardening: `.htaccess` front controller, `$debug`-gated error disclosure (was `app.env`/`app.debug`) with `error_log` fallback.
- `.env` environment template (RADYK-style dot-notation keys).
- Project documentation: completed `CLAUDE.md` (Dev URL, Modular Architecture, Blade-shaped View System, Directory Layout, File Boundary, Secure Coding Rules) and new `docs/RULES.md`, `docs/PLAN.md`, `docs/PRD.md`, `docs/REPORT.md`, `docs/CHANGELOG.md`; expanded `README.md`.
- `docs/PLAN.md` track log: plan-vs-code status verification records (`>track` runs).
- PRD/PLAN coverage for the `app.modules` env toggle: flat vs modular `apps/` layout, default `false` when key missing, everything except `Manifest.json` moves flat, single `App\` PSR-4 root (PRD FR-11, PLAN Phase 0b, decision D6).
- `.env` docblocks for `app.modules` and `app.mode` (available values, defaults, effects); `app.mode` coverage in PRD FR-12 and PLAN decision D8 (next to Phase 4 headless/SSR work).
- `.gitignore` for local-only files (`.env`, `.envrc`, `.vscode/`, `sql/local.sql`, `docs/CONFIG.md`, `/vendor/`).
- `tests/` directory for all test/smoke-check files (decision D7); bootstrap smoke check at `tests/smoke_check.php`, self-contained from any working directory.
- Phase 0b — `app.modules` layout toggle (decisions D6/D9/D10, PRD FR-11/FR-13): two-root autoload. Namespaces `nsapp=`App`, `nsmod=`Modules`; path roots `apps/` (main MVC) and `modules/` (optional HMVC). `app.modules=true` registers the per-module templates under `modules/%s/…`; `false`/absent leaves roots resolving but templates unregistered. All registered in `.structures`, resolved by `System\Kernel\Boot::autoloader`.
- Phase 0b layout verification added: `tests/layout_check.php` toggles `.env` `app.modules=true`/`false` (auto-restores `.env`), boots the real entry in a subprocess per mode, asserts constants + autoload resolution. Companion probe: `tests/bootstrap_probe.php`.

### Changed

- `.config` — registers `appf`/`modulesf`/`systemf` from entry vars + namespaces `nsapp`/`nsmod`/`nssys`; structural guard requires `modulesf` + `kernelf`.
- `public/nanoradf` — failure handler: `$code = $th->getCode() ?: 500`; error appended to `<root>/storage/logs/system.log` via `error_log(…, 3, $path)` (auto-mkdir, fallback to default log if unwritable); generic body unless `$debug`; 404 guard requires all entry folder vars (`||`).
- `public/nanoradf` — error path uses plain concatenation (`basepath . '/storage/logs/system.log'`, `"{$log_dir}/system.log"`) instead of `interlace()`; double separator tolerated by Windows paths.
- `.structures` — Phase 0b layout toggle (D6/D9/D10): root namespaces `nsapp`=`App`, `nsmod`=`Modules`; flat app folders (`configfapp`, `controllersfapp`, `modelfapp`, `servicesfapp`, `viewsfapp`); per-module templates (`configfmod`, `controllersfmod`, `modelfmod`, `servicesfmod`, `viewsfmod`) only when `app.modules=true`.
- `src/Kernel/Boot.php` — `autoloader()` two roots: `nssys`→`src/`, `nsapp`→`apps/`, `nsmod`→`modules/`.
- `public/index.php` — declares `$modules_folder = 'modules'`; `$debug` master disclosure flag (default false).
- `public/nanoradf` — gateway (renamed from `public/nanoradf.php`).
- `.structures` — app map gains flat-mode folders (`controllersfapp`, `modelfapp`, `viewsfapp` + namespace constants); new per-module folder/namespace template map (`configf`, `controllersf`, `modelf`, `servicesf`, `viewsf` with `%s` module-name placeholders); explicit `else {continue;}` guards; 404 guard.
- `public/nanoradf.php` — 404 when an entry point sets neither `$application_folder` nor `$system_folder`; decides the `appdebug` constant before boot; `message in file:line` detail only when `appdebug === true` (generic message otherwise; details always to `error_log`).
- `src/Kernel/Boot.php` — `initiate()` no longer defines `basepath`/`publicpath`/`sep` nor re-requires `.supports`/`.structures`; the entry chain owns bootstrap, the kernel only registers the autoloader.

### Fixed

- `System\Kernel\Boot::autoloader` read undeclared static properties `self::$nssys` / `self::$nsmod` — first class load threw `Error: Access to undeclared static property`. Now reads the global namespace constants registered by `.config` / `.structures`.
- `System\Kernel\Boot::autoloader` resolved module paths from undefined constant `modulesf`; the constant defined by `.structures` is `modulesfapp`. First module class load threw `Error: Undefined constant`. Now resolves via the `modulesfapp` constant.
