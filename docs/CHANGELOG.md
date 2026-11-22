# Changelog

All notable changes to NanoRADF are documented here.
Format: [Keep a Changelog](https://keepachangelog.com/en/1.1.0/). Versioning: [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added
- Phase 2 batch 3 — Pattern-based mask URL engine: `mask_urls` config uses flexible regex patterns with `{*}` path capture and `{face_theme_active_name}` / `{bone_theme_active_name}` placeholders; pattern matching in AssetsService::resolve() supersedes static slug map. Theme-aware asset serving: face routes resolve under public/themes/{theme}/, bone routes under src/Themes/{theme}/ with separate queue dedup via `$queuedFaceAssets` / `$queuedBoneAssets`.
- Helper auto-autoload registry: new `apps/Config/Helper.php` stem list (`['uri', 'utilities']`) loaded by `.starter` after config, before kernel init, ensuring helpers are available when classes start loading. File naming convention aligned to `*Helper.php` suffix (UriHelper, UtilitiesHelper).
- Auto head/foot injection: `Views::render()` now merges default asset tags (`head`, `foot`, `bone_head`, `bone_foot`) into template scope from AssetsService; controller no longer needs manual assignment. WelcomeController cleaned of redundant $head/$foot lines.
- Drip directive engine extension: `@enqueueStyles` → `AssetsService::renderFaceStyles()`, `@enqueueScripts` → `AssetsService::renderFaceScripts()`, `@style` / `@script` for inline blocks (fully qualified namespace in compiled PHP).
- Dispatcher-level asset interception: `Dispatcher::handle()` intercepts masked assets BEFORE Router dispatch; private static `asset()` method serves with proper headers (Content-Type, ETag, Cache-Control, Content-Length); no route entries needed for assets.
- Error pages from `src/Views/Errors/`: 404.html and 500.html created as standalone HTML templates with error.css styling; `Dispatcher::errorPage()` renders them via Drip (no layout wrapping) using hardcoded paths; router throws RuntimeException on 404/405, dispatcher catches and delegates to themed error rendering.
- CSS token design system collapsed: 8 modular partial files (color, space, type, elevation, motion, media, form, nav) merged into single `System.css` (~11.5KB) containing all design tokens; theme folder renamed from admin → default.
- Theme default configuration: `src/Config/Theme.php` defines active theme names per surface (face/bone), defaults to 'default'.
- Config helper and Theme helper: `ConfigHelper.php` (gconf()), `ThemeHelper.php` (activeTheme()) providing typed accessors for app and theme settings.

### Changed
- Echo syntax finalised as `{{$var}}` (escaped) / `{{!$var!}}` (raw); the earlier `|raw` filter idea was dropped — no filter machinery, no `e()` helper. Manual escape functions inside `{{ }}` now throw at compile time instead of silently double-escaping.
- View folders under `apps/Views` are lowercase: a leading capital letter always means a module, so `Layouts.main` would be read as module `Layouts`. `src/Views` keeps its capitalised taxonomy, reached through constants rather than dot-notation.

### Changed
- NFR-1 strengthened: the framework is fully self-contained — no Composer packages, plugins, or third-party runtime/build dependency is required to run, route, dispatch, or render; PSR-4 via `System\Kernel\Boot::autoloader`, no `vendor/`. Dev-time tooling (if any) is optional and never a runtime requirement. Background: every `require`/`use` in the codebase resolves to the app's own namespaces, so no package registry or plugin loader exists.

### Added
- Phase 1 — routing & controllers: `src/Kernel/Router.php` and `src/Kernel/Controller.php`, base controllers `PublicController`, `PrivateController`, `ApiController`, route table `apps/Config/Routes.php` wired through the gateway. Route keys are flat `'GET /path'` => `Ctrl::class.'::action'`, with prefix groups (`'/admin' => [ 'GET /dash' => ... ]`; inner keys must be full `METHOD /path`); exact-before-parameterised; browser-probed `/`, `/hello/{name}`, `/ping`, `/status/{code}`, `POST /` (405).
- API envelope contract: api-surface `respond()` always emits JSON and sets `Content-Type: application/json; charset=utf-8`. `/status/{code:[45]\d\d}` returns the error envelope `{"ok":false,"error":"scoped failure"}` for a real API error path.
- Gateway simplicity (2026-11-22): dispatch moved to `src/Kernel/Dispatcher.php`; one catch-all in `nanoradf` delegates to `blackwall()` from `.starter`; new error page asset `public/themes/default/css/error.css`.

### Removed
- `.supports` → `read_directories()` (dead code, no caller since `public/index.php` stopped declaring `$directories`; REPORT #4 closed).
- `src/Kernel/Boot.php::initiate()` no longer emits the leftover `echo 'boot initiated!'` debug line (it leaked into every response body). `.config` drops unused entry locals and keeps only constants.

### Changed
- Gateway: one catch-all (`Dispatcher` for the happy path, `blackwall()` for faults). Disclosure per `is_debug()` (driven by `app.debug`); `APP_DEBUG` constant removed.
- Router capture groups are canonicalized to positional slots so sibling patterns with different placeholder names cannot collide.
- Route table: flat `'GET /path'` keys; prefix group key is the prefix; inner keys must be full `METHOD /path`; `Allow:` derived from the table; `HEAD` served from the GET candidate with an empty body.

### Fixed
- Route named-group collision (e.g. `/x/{name}` vs `/x/{id:\d+}`) captured the wrong value; fixed with canonical capture slots. Covered by `tests/route_check.php` and commit `9371800`.
- API media type: `respond()` on the api surface emits JSON with `Content-Type: application/json; charset=utf-8`; the gateway now applies response headers before the status line so HTML vs JSON never collide.
- The gateway's runtime error branch is gated by `APP_DEBUG` (from `app.debug`); the earlier entry-level `$debug` switch was removed so `app.debug` drives disclosure again (no duplicate/conflicting master). Logs status-normalised entries; bootstrap sentinels keep their friendly messages.
- Two-root autoload contract (D10, Phase 0b): `App\…` → `apps/` and `Modules\…` → `modules/` always active; namespaces `nssys`/`nsapp`/`nsmod`; `app.modules` gates the per-module templates (`configfmod`, `controllersfmod`, `modelfmod`, `servicesfmod`, `viewsfmod`). `tests/layout_check.php` asserts both roots resolve + template gate.

### Fixed
- Audit of the refactored bootstrap (`index.php`, `nanoradf`, `.config`, `.structures`, `Boot.php`): removed a fatal `Undefined constant "nsappmod"` in the autoloader/tests (`nsappmod`/`appmodf` were never defined), removed the `echo` debug leak in `.structures`, corrected modular namespace to `Modules\`, made `app.modules` functional again, and gated error disclosure with `$debug` in `index.php` (was hardcoded true, breaking FR-10).
- `.starter` / `genv` dotenv consolidation: `.starter` is now the single `.env` parser (dotted keys, first-wins, inline `#` strip, silent skip when missing; registers via `putenv()` + `$_ENV`); `genv()` became a `getenv()` wrapper with `enval()` typing — removed its duplicate file parser, static cache, and `env_clear_cache()` (no callers).

### Added

- Project bootstrap chain: `public/index.php` → `nanoradf` → `.config` → `.supports` → `.structures` → `src/Kernel/Boot.php` with `initiate()` guard and gated error output.
- Global helper library in `.supports` (`is`, `urlify`, `namespacify`, `pathify`, `interlace`, `gserver`, `enval`, `genv`, `statc`, `fl`).
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

- `nanoradf` — gateway slimmed (+19/−39): guarded `.starter` load (`$error='starter'`), version gate / `.supports` / `.config` loading delegated to the `.starter` sentinel chain, kernel hand-off reduced to a single `$error` throw + `initiate()`; catch maps sentinel codes (`starter`, `phpmin`, `common`, `config`, `structure`, `boot`, `kernel`) to friendly messages, `APP_DEBUG` appends `file:line`.
- Gateway relocated: `public/nanoradf` → project-root `nanoradf` (entry `public/index.php` now requires it); `.starter` (dotenv loader) required before `.supports`.
- `.supports` — helper renames: `__f()`→`fl()`, `__env()`→`genv()`, `__server()`→`gserver()`, `cast_value()`→`enval()`, `__s()`→`statc()`; call sites in `.config`, `nanoradf`, `.structures`, and `tests/bootstrap_probe.php` updated.
- `CLAUDE.md` — `>branch` custom command now follows the Branching Model: derive `<type>/<slug>` from the task (`feat`/`fix`/`hotfix`/`patch`), verify unused local + `origin`, create from the `dev` tip, and switch.

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
