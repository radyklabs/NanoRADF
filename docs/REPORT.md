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
### 2026-11-24 — Test suite rebuild & failure analysis (feat/phase-2)

- **Branch / commit:** `feat/phase-2` (commit `8cafa8c`); working tree clean after push.
- **Scope:** `tests/` folder — deleted all 8 legacy test files, rebuilt 8 new test files aligned with current codebase (namespace `System\` helpers, `namespace System;` dotfiles).
- **Tools:** PHP 8.3.36 (LocalWP) CLI; `tests/smoke_check.php`, `tests/route_check.php`, `tests/view_check.php`, `tests/assets_check.php`, `tests/layout_check.php`, `tests/bootstrap_probe.php`, `tests/url_exists_test.php`, `tests/urlprettify_test.php`.

#### Pass-after summary

| Test | Result | Details |
|------|--------|---------|
| `smoke_check.php` | PASS | Boot loads, all constants defined, both autoload roots resolve |
| `route_check.php` | PASS | 15/15 checks pass (flat routes, prefix groups, params, regex, method mismatch, normalisation, malformed targets) |
| `layout_check.php` | PASS | Both `app.modules=true`/`false` modes verified; two-root model holds |
| `bootstrap_probe.php` | PASS | Constants + autoload roots correct for both modes |
| `url_exists_test.php` | PASS | Local/remote URL checks behave as expected |
| `urlprettify_test.php` | PASS | 6/6 URL construction cases pass |
| `view_check.php` | FAIL | 14/16 pass; 2 fail (module prefix resolution) |
| `assets_check.php` | FAIL | 15/24 pass; 9 fail (enqueue/render/helpers) |

---

### Findings — view_check.php (2 failures)

| # | Class | Severity | Location | Finding | Root cause |
|---|-------|----------|----------|---------|------------|
| V1 | CONFIRMED | medium | `tests/view_check.php:22` | `module prefix resolves under modules/` — `ViewsService::path('Welcome.test')` does not throw as expected | `ViewsService::path()` checks `genv('app.modules') !== true` before throwing for module paths. `genv('app.modules')` returns `true` (from `.env`), so module resolution proceeds instead of throwing. Test expects an exception but none is thrown. |
| V2 | CONFIRMED | medium | `tests/view_check.php:23` | `module context applies to plain names` — `ViewsService::path('test', 'Welcome')` does not throw as expected | Same root cause as V1: `genv('app.modules')` returns `true`, so context-based module resolution proceeds instead of throwing. |

**Impact:** Module-view resolution works correctly when `app.modules=true`; the test expectations assume module resolution throws, which contradicts the current (correct) behavior. Test expectations need updating to match actual behavior, OR `app.modules` needs to be `false` for those specific assertions.

**Remediation:** Update test expectations to match actual behavior (module resolution succeeds when `app.modules=true`), or temporarily set `app.modules=false` for those two assertions.

---

### Findings — assets_check.php (9 failures)

| # | Class | Severity | Location | Finding | Root cause |
|---|-------|----------|----------|---------|------------|
| A1 | CONFIRMED | medium | `tests/assets_check.php:22` | `face styles queue style.css and System.css` — `AssetsService::styles('face')` returns wrong array | `apps/Config/AssetsConfig.php` has `enqueue_styles => ['style']` but `src/Config/AssetsConfig.php` has `enqueue_styles => []`. `gconfig('assets')` merges both via `array_replace_recursive`, but the app-level override replaces the top-level key entirely, leaving `enqueue_styles => []`. |
| A2 | CONFIRMED | medium | `tests/assets_check.php:23` | `face scripts queue app.js` — `AssetsService::scripts('face')` returns wrong array | Same root cause as A1: `enqueue_scripts => []` in both configs; test expects `['js/app.js']`. |
| A3 | CONFIRMED | medium | `tests/assets_check.php:24` | `bone styles queue style.css` — `AssetsService::styles('bone')` returns wrong array | Same root cause: `bone_enqueue_styles => []` in both configs; test expects `['css/style.css']`. |
| A4 | CONFIRMED | medium | `tests/assets_check.php:25` | `runtime enqueue is deduped` — dedup test fails | Same root cause: base queue is empty, so enqueue doesn't produce expected result. |
| A5 | CONFIRMED | medium | `tests/assets_check.php:28` | `face styles link the masked css and tokens` — `renderFaceStyles()` output wrong | Depends on A1/A2: empty queue → no `<link>` tags rendered. |
| A6 | CONFIRMED | medium | `tests/assets_check.php:29` | `face scripts defer-load the masked js` — `renderFaceScripts()` output wrong | Depends on A1/A2: empty queue → no `<script>` tags rendered. |
| A7 | CONFIRMED | medium | `tests/assets_check.php:30` | `bone styles link the bone css` — `renderBoneStyles()` output wrong | Depends on A3: empty queue → no `<link>` tags rendered. |
| A8 | CONFIRMED | medium | `tests/assets_check.php:31` | `bone scripts link the bone js` — `renderBoneScripts()` output wrong | Depends on A3: empty queue → no `<script>` tags rendered. |
| A9 | CONFIRMED | medium | `tests/assets_check.php:112` | `e() escapes` — `e('<b>')` returns wrong result | `e()` function exists globally (loaded from `UtilitiesHelper.php` via helper registry) but the test assertion `e('<b>') === '<b>'` is wrong — `e()` escapes HTML, so `e('<b>')` returns `'<b>'`, not `'<b>'`. Test assertion is incorrect. |

**Impact:** Asset enqueue/render pipeline works correctly when config has proper values; the failures stem from config mismatch between `apps/Config/AssetsConfig.php` (empty queues) and test expectations (non-empty queues), plus one incorrect test assertion.

**Remediation:** Either populate `apps/Config/AssetsConfig.php` with the expected enqueue values, or update test expectations to match current config. Fix A9 assertion to `e('<b>') === '<b>'`.

---

### Verification (fail-before / pass-after)

**Fail-before (pre-rebuild):** All 8 legacy test files referenced undefined global functions (`interlace`, `genv`, `urlify`, `e`, `baseurl`) because dotfiles were moved to `namespace System;` — every test exited 255 with `Call to undefined function`.

**Pass-after (current):**
- 6/8 test suites pass fully
- `view_check.php`: 14/16 pass (2 module-resolution expectation mismatches)
- `assets_check.php`: 15/24 pass (8 config-mismatch failures + 1 assertion error)

**Decisions recorded:** None new — existing D1–D5 in PLAN.md apply.

**Remediation plan for open findings:**
- V1/V2: Update `tests/view_check.php` expectations to match `app.modules=true` behavior, or set `app.modules=false` for those assertions.
- A1–A8: Populate `apps/Config/AssetsConfig.php` enqueue lists to match test expectations, or update test expectations to match current empty config.
- A9: Fix test assertion to `e('<b>') === '<b>'`.
