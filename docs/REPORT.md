# Report

Dated findings, scans, and audit entries. Evidence only — nothing is claimed fixed until the fix plus verification is recorded here.

## 2026-11-20 — Baseline scan & kernel autoloader fix

- **Branch / commit:** `main` (pre-change state: single `Initial commit`, working tree untracked).
- **Scope:** full workspace scan — 44 files. Logic reviewed end to end: `.supports`, `.config`, `.structures`, `src/Kernel/Boot.php`, `public/index.php`, `public/nanoradf.php`, `public/.htaccess`, `.env`, `.envrc`, `.vscode/settings.json`, `sql/local.sql` (header/schema inspection only).
- **Excluded from logic review:** 29 zero-byte scaffolds (`apps/*`, most of `src/*`, `public/themes/*`) — no code to review yet; `docs/` was empty at scan time.
- **Tools:** PHP 8.3.36 (LocalWP) CLI; `git status`/`log`; manual line-by-line read. No static analyzers/test runners exist yet.

### Phase 0b verification — 2026-11-20 (`app.modules` layout toggle)

- **Branch:** `phase-0b`, from `dev` (`d3e875b`).
- **Command:** `php tests/layout_check.php` — backs up `.env`, toggles `app.modules=true`/`false`, boots the real entry (`public/index.php`) in a subprocess per mode, asserts the resolved constants + autoload root; pass = exit 0. Baseline `php tests/smoke_check.php` (exit 0) + `http://nanoradfw.local` → 200 re-run after the change.
- **Fail-before (pre-change, old bootstrap):**

```text
--- app.modules=true ---   (flat apps/ present)
modulesfapp=...\apps\Modules   controllersfapp=...\apps\Controllers   module=NO
--- app.modules=false ---
nsappmod=App\Modules (want App) · modulesfapp defined (want UNDEF) · routes=NO
LAYOUT-CHECK-FAIL   exit=1
```

- **Root cause:** `.structures` registered the modular tree (`modulesfapp`, per-module templates) unconditionally and the flat folders separately, while the autoloader special-cased `App\Modules` via `nsappmod`=`App\Modules` + `modulesfapp`. `app.modules` was read nowhere → the toggle had no effect, and flat `App\...` classes could never resolve.
- **Pass-after (Phase 0b):** `nsappmod`/`appmodf` switch `App\Modules`/`apps/Modules` ↔ `App`/`apps`; flat folders register in both layouts; modular tree only when `app.modules=true`; autoloader resolves through one root.

```text
--- app.modules=true ---
nsappmod=App\Modules · appmodf=...\apps\Modules · modulesfapp=SET · controllersfapp=SET
path=...\apps\Modules\Welcome\Controllers\WelcomeController.php · resolve=YES
--- app.modules=false ---
nsappmod=App · appmodf=...\apps · modulesfapp=UNDEF · controllersfapp=SET
path=...\apps\Config\Routes.php · resolve=YES
LAYOUT-CHECK-PASS   exit=0   (`.env` restored, byte-identical)
```

### Findings

| # | Class | Severity | Location | Finding | Status |
|---|-------|----------|----------|---------|--------|
| 1 | CONFIRMED | critical | `src/Kernel/Boot.php:78` (pre-fix) | `autoloader()` used `self::$nssys` / `self::$nsmod` — static properties never declared anywhere. Any class load → `Error: Access to undeclared static property System\Kernel\Boot::$nssys`. | ✅ fixed (D3) |
| 2 | CONFIRMED | critical | `src/Kernel/Boot.php` (pre-fix, module branch) | Module path built from undefined constant `modulesf`. `.structures` defines `modulesfapp` (loop `$appfs`, key `modulesfapp`). First `App\Modules\...` load → `Error: Undefined constant "System\Kernel\modulesf"`. Surfaced only after fixing #1. | ✅ fixed (D3) |
| 3 | CONFIRMED | low | `public/nanoradf.php` (comment above `define ('basepath', ...)`) | Stale comment: "`basepath` stays /public (mirrors BoneRADF contract)" — code sets `basepath` = project root and `publicpath` = `/public`. Comment contradicts behavior. | ⚠️ open (out of requested scope) |
| 4 | CONFIRMED | info | `.supports` → `read_directories()` | No active caller: `public/index.php` no longer declares `$directories`; the consuming block in `Boot::initiate()` is commented out. Dead path pending Phase 1 decision (keep for CLI consumers or remove). | ⚠️ open (tracked in PLAN "Open risks") |
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
- #4: decide keep-or-remove `read_directories()` in Phase 1; if kept, give it a caller or mark `@internal`.
- #6: define the `apps/Services` namespace contract in Phase 1 and extend the autoloader accordingly; record in PLAN + CHANGELOG.
