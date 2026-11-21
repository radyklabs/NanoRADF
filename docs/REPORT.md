# Report

Dated findings, scans, and audit entries. Evidence only — nothing is claimed fixed until the fix plus verification is recorded here.

## 2026-11-20 — Baseline scan & kernel autoloader fix

- **Branch / commit:** `main` (pre-change state: single `Initial commit`, working tree untracked).
- **Scope:** full workspace scan — 44 files. Logic reviewed end to end: `.supports`, `.config`, `.structures`, `src/Kernel/Boot.php`, `public/index.php`, `public/nanoradf.php`, `public/.htaccess`, `.env`, `.envrc`, `.vscode/settings.json`, `sql/local.sql` (header/schema inspection only).
- **Excluded from logic review:** 29 zero-byte scaffolds (`apps/*`, most of `src/*`, `public/themes/*`) — no code to review yet; `docs/` was empty at scan time.
- **Tools:** PHP 8.3.36 (LocalWP) CLI; `git status`/`log`; manual line-by-line read. No static analyzers/test runners exist yet.

### 2026-11-20 — Post-refactor audit (user change set)

- **Branch:** `phase-0b`; **Scope:** `public/index.php`, `public/nanoradf` (renamed from `.php`), `.config`, `.structures`, `src/Kernel/Boot.php`; **Users' pre-existing edits preserved** (added `$modules_folder`, moved `apps/Modules/Welcome` → `modules/Welcome`, renamed gateway).
- **Tools:** PHP 8.3.36 CLI, `tests/smoke_check.php`, `tests/layout_check.php`, `http://nanoradfw.local` (nginx), `git diff`.

| # | Sev | Location | Finding | Status |
|---|-----|----------|---------|--------|
| A1 | critical | `.structures` (old flat branch, commented) + `Boot.php`/`tests` | `nsappmod`/`appmodf` never defined after the constant rename → `Undefined constant` fatal in autoloader + tests (exit 255). | ✅ fixed (D10) |
| A2 | critical | `.structures:41` + `Boot.php:53` | `app.modules` no longer gated anything; both roots autoloaded unconditionally (toggle dead). | ✅ fixed (D10) |
| A3 | critical | `.structures:34` | live `echo` leaked internal paths/namespaces into the HTTP body (`configfapp : …<br/>…`). | ✅ fixed |
| A4 | critical | `.config:34` (`nsmod`) | namespace root `Modules` (segment) — `str_starts_with('App\Modules\…', 'Modules')` false; modular classes never resolved. | ✅ fixed (`nsmod`=`Modules\`) |
| A5 | medium | `public/index.php:12` + `nanoradf` | `$debug=true` hardcoded; `app.env`/`app.debug` ignored → detail always disclosed. FR-10 broken. | ✅ fixed (`$debug` master; `app.debug` dropped) |
| A6 | low | `nanoradf:6` | 404 guard omitted `$modules_folder`. | ⚠️ open (entry always sets it; hardening) |
| A7 | low | `nanoradf` | `error_log` message relative; use `interlace(basepath,'storage/logs/…')` when logging lands. | ⚠️ open |

**Root cause:** constant contract renamed in `.config`/`Boot` (`nsappmod`/`appmodf`) but the defining branch in `.structures` was commented out, leaving both undefined; `nsmod` was defined as a path segment, not a namespace prefix; `app.modules` read nowhere.

**Fix (D10):** two-root contract `App\…`→`apps/`, `Modules\…`→`modules/` always active; `app.modules` gates only the per-module templates; debug via `$debug`. Fail-before/pass-after recorded below.

```text
Fail-before: app=NO mod=NO | smoke PHP Fatal Undefined constant "nsappmod" exit=255 | body leaked echo
Pass-after:  mode=true  → nsapp=App nsmod=Modules app_resolve=YES mod_resolve=YES templates=REGISTERED
             mode=false → nsapp=App nsmod=Modules app_resolve=YES mod_resolve=YES templates=IGNORED
             smoke exit=0 · layout PASS exit=0 · http 200 len=0
```

### Phase 0b verification — 2026-11-20 (`app.modules` layout toggle)

- **Branch:** `phase-0b`, from `dev` (`d3e875b`).
- **Command:** `php tests/layout_check.php` — backs up `.env`, toggles `app.modules=true`/`false`, boots the real entry (`public/index.php`) in a subprocess per mode, asserts the resolved constants + autoload root; pass = exit 0. Baseline `php tests/smoke_check.php` (exit 0) + `http://nanoradfw.local` → 200 re-run after the change.

#### Pass-after (D10 — two roots, root `modules/`)

```text
--- app.modules=true ---
nsapp=App · nsmod=Modules · app_resolve=YES · mod_resolve=YES · mod_templates=REGISTERED
--- app.modules=false ---
nsapp=App · nsmod=Modules · app_resolve=YES · mod_resolve=YES · mod_templates=IGNORED
```

`php tests/smoke_check.php` → exit 0; `http://nanoradfw.local` → 200 (len=0, no leak).

#### Root cause

`.structures` registered the modular tree (`modulesfapp`, per-module templates) unconditionally and the flat folders separately, while the autoloader special-cased `App\Modules` via `nsappmod`=`App\Modules` + `modulesfapp`. `app.modules` was read nowhere → the toggle had no effect, and flat `App\...` classes could never resolve.

**D9→D10 correction (user + audit):** module HMVC at project-root `modules/`; `apps/` always the main MVC. `.config` registers `modulesf`; `.structures` defines namespaces `nsapp`/`nsmod`; `Boot::autoloader` resolves two roots (`App\…`→`apps/`, `Modules\…`→`modules/`). `app.modules` gates only the per-module templates.

### Findings

| # | Class | Severity | Location | Finding | Status |
|---|-------|----------|----------|---------|--------|
| 1 | CONFIRMED | critical | `src/Kernel/Boot.php:78` (pre-fix) | `autoloader()` used `self::$nssys` / `self::$nsmod` — static properties never declared anywhere. Any class load → `Error: Access to undeclared static property System\Kernel\Boot::$nssys`. | ✅ fixed (D3) |
| 2 | CONFIRMED | critical | `src/Kernel/Boot.php` (pre-fix, module branch) | Module path built from undefined constant `modulesf`. `.structures` defines `modulesfapp` (loop `$appfs`, key `modulesfapp`). First `App\Modules\...` load → `Error: Undefined constant "System\Kernel\modulesf"`. Surfaced only after fixing #1. | ✅ fixed (D3) |
| 3 | CONFIRMED | low | `public/nanoradf.php` (comment above `define ('basepath', ...)`) | Stale comment: "`basepath` stays /public (mirrors BoneRADF contract)" — code sets `basepath` = project root and `publicpath` = `/public`. Comment contradicts behavior. | ⚠️ open (out of requested scope) |
| 4 | CONFIRMED | info | `.supports` → `read_directories()` | No active caller: `public/index.php` no longer declares `$directories`; the consuming block in `Boot::initiate()` is commented out. Dead path pending Phase 1 decision (keep for CLI consumers or remove). | ✅ resolved 2026-11-21 (Phase 1): removed from `.supports` with a CHANGELOG entry; no caller existed. |
| 5 | CONFIRMED | info | `sql/local.sql` | Leftover WordPress (`wp_*`) dump from LocalWP MySQL 8.4 — not a project asset. | ✅ resolved (D2: gitignored, not deleted) |
| 6 | HYPOTHESIS | medium | `apps/Services/` (empty) + autoloader | Shared app services have no namespace prefix in the autoloader (`System\` and `App\Modules\` only); classes dropped there will not autoload. Needs decision when Phase 1 wires services. | ⚠️ open (PLAN risk #1) |

### Verification (fail-before / pass-after)

Fail-before (pre-fix), PHP CLI:

```text
> php -r "require 'index.php'; class_exists('System\Helper\Utilities');"
PHP Fatal error: Uncaught Error: Access to undeclared static property
System\Kernel\Boot::$nssys in D:\Www\nanoradf\app\src\Kernel\Boot.php:78
exit=255
```

First re-run after fix #1 exposed finding #2:

```text
PHP Fatal error: Uncaught Error: Undefined constant "System\Kernel\modulesf"
in D:\Www\nanoradf\app\src\Kernel\Boot.php:98
exit=255
```

Pass-after (both fixed), script asserts the autoloader includes the correct mapped files for `System\Helper\Utilities` and `App\Modules\Welcome\Controllers\WelcomeController`:

```text
BOOT-LOADED
autoload sys=YES mod=YES
exit=0
```

### Decisions recorded

D1–D5 recorded in `docs/PLAN.md` → Decision log (dev URL, `sql/local.sql` ignored, fix-now, English docs, plan-first).

### Remediation plan for open findings

- #3: correct the comment when `nanoradf.php` is next touched (Phase 1); no behavior impact.
- #4: resolve `read_directories()` in Phase 1 — **resolved 2026-11-21**: removed from `.supports` (no caller; documented free-text helper list entry updated).
- #6: define the `apps/Services` namespace contract in Phase 1 and extend the autoloader accordingly; record in PLAN + CHANGELOG.
