# RULES — NanoRADF Conventions

Read this before touching the repository. It is the normative companion to `CLAUDE.md`.

## 1. Runtime & style

- PHP 8.1+, `declare (strict_types=1);` is the first statement of every PHP file.
- **Drip Template Engine**: the view engine. `View::render()` accepts view files only in `.html` (the extension is appended to dot-notation names); directives are Blade-shaped — `@extends`, `@yield`, `@section`/`@endsection`, `@include`; `{{ }}` is auto-escaped, with a `|raw` opt-out.
- **Zero dependencies / self-contained** (NFR-1): no Composer packages, plugins, or third-party runtime/build dependency. Class loading is only `System\Kernel\Boot::autoloader`; there is no `vendor/` and no plugin loader. Any dev-time manifest must stay optional and never be required to run the framework.
- Formatting conventions observed across the codebase — follow them, do not reformat foreign code:
  - Space before call parentheses: `interlace (...)`, `is_readable ($path)`.
  - Space after control keywords: `if (…)`, `foreach (…)`, `while (…)`.
  - Braces on their own line for functions/classes/`if`/`foreach` bodies; single-line bodies allowed only for trivial one-liners.
  - Single quotes for strings unless interpolation or escapes are required.
  - 4-space indent, LF line endings (`.gitattributes`: `* text=auto`).
- Naming:
  - Classes: PascalCase, one class per file, `final` where extension is not designed.
  - Methods/properties: camelCase.
  - Global helper functions: snake_case or short lowercase (`namespacify`, `genv`, `gserver`, `enval`, `statc`, `fl`).
  - Global constants: lowercase (`basepath`, `sep`, `systemf`, `nssys`).
- Prefer the existing helpers in `.supports` (`interlace`, `pathify`, `is`, `genv`) over ad-hoc path/string logic.

## 2. Placement & boundaries

- `src/` = framework core (`System\...`). `apps/` = application code (`App\Modules\...`). Never mix the two (see `CLAUDE.md` → File Boundary).
- One class per file; the PSR-4 path must match the autoloader mapping or the class will never load.
- Module folder contract: `Manifest.json`, `Config/`, `Controllers/`, `Model/`, `Services/`, `Views/` (see `CLAUDE.md` → Modular Architecture).
- Views are HTML files in role folders (`Components/`, `Elements/`, `Layouts/`, `Patterns/`, `Errors/`, `Scripts/`, `Tokens/`) — no PHP business logic inside view files.
- No new top-level directories or scaffold renames without a recorded decision in `docs/PLAN.md` + `docs/CHANGELOG.md`.

## 3. Bootstrap dotfiles (fragile)

`.supports` → `.config` → `.structures` → `src/Kernel/Boot.php` must keep loading in that order. After ANY edit to these files or `public/index.php` / `public/nanoradf`, run the boot smoke check before committing:

```powershell
& 'C:\Users\Rahen\AppData\Roaming\Local\lightning-services\php-8.3.36+0\bin\win64\php.exe' D:\Www\nanoradf\app\tests\smoke_check.php
```

The check lives in `tests/` (decision D7: all test files under `/tests`) and is self-contained — it `chdir`s to `public/` itself; pass = `exit 0`. Including `public/nanoradf` directly now exits 404 by design (entry variables missing), so always test through `public/index.php` as the script does.

Fixing ≠ trimming: a failing line is root-caused and fixed, never deleted or stubbed to hide the trigger. Always keep a failing-before / passing-after record (command + output) in `docs/REPORT.md`.

## 4. Documentation sync

Four files move together: `docs/PLAN.md`, `docs/PRD.md`, `docs/REPORT.md`, `docs/CHANGELOG.md`. A change to behavior, scope, or decisions updates all that apply — in the same change set.

- `PLAN.md` — phased plan + status + decision log.
- `PRD.md` — product requirements/acceptance criteria only (edit when requirements actually change).
- `REPORT.md` — dated findings/audit entries with file:line evidence.
- `CHANGELOG.md` — Keep a Changelog format, everything under `[Unreleased]` until release.

Project docs are written in **English** (decision D4, `docs/PLAN.md`).

## 5. Security baseline

Follow `CLAUDE.md` → Secure Coding Rules without exception. Extra operational notes:

- `.env`, `.envrc`, `sql/local.sql`, `.vscode/`, `docs/CONFIG.md` are gitignored — never force-add them (`git add -f` is forbidden).
- Never log or echo tokens, `.env` values, or full backend responses in dev output.

## 6. Git & commits

- Branches: `main` (release-ready), `dev` (working), `feat/<slug>`, `fix/<slug>`, `hotfix/<slug>`, `patch/<slug>` cut from `dev`.
- Conventional commits: `feat:`, `fix:`, `docs:`, `refactor:`, `test:`, `chore:`, `security:`.
- Never include `Co-authored-by:` trailers (any tool, any source).
- Commit completed work immediately; push the current branch to `origin` when done.
- One task = one change set. No drive-by refactors, no formatting of unrelated files.

## 7. Verification floor

- No test framework yet: every non-trivial logic change leaves ONE runnable check, run and recorded before commit. **All test files live in `/tests`** (decision D7) — never in `public/` or `src/`.
- Trivial one-liners need no test.
- Documentation-only changes need no runtime validation.
- Web check: `http://nanoradfw.local` must render without a 500 after bootstrap-touching changes.

- Verify with the project's real checks (§3/§7) and web probes after each incremental step, not only at the end.

## 9. SOP — phase kickoff & change loop

Use this before starting any phase or sizable task. Keep it short: align first, touch code second.

### 9.1 Read before asking
1. Re-read the target phase in `docs/PLAN.md`, its FR/NFR lines in `docs/PRD.md`, and any related decisions in the PLAN decision log.
2. Read the **actual base code** that the task touches and every path related to it (entry chain, gateway, router/controller, view/theme scaffolding, tests). Map who calls whom.
3. Record what "works today" and why each relevant line exists — no assumptions, no hallucinated behaviour.

### 9.2 Align with the requester (interactive)
Ask a multiple-choice question set when the approach would change the shape of the work: one sentence per question, a default recommendation with reasons, and an explicit free-form slot. Cover: data shape/API, extension/language, location/naming, config vs code, security policy, verification artefacts, and where the SOP lives. Wait for answers before coding.

### 9.3 Execute in small verified steps
- One focused change per commit; keep diffs surgical (one task = one change set).
- After each step run the smallest check that covers it (`tests/*`, lint, web probe `http://nanoradfw.local`).
- If a step needs more than expected, stop: restore state (`git stash`/checkout), re-plan, re-run after fixing cause — never pile edits.

### 9.4 Sync docs in the same change set
Update `docs/PLAN.md` (status/track log), `docs/CHANGELOG.md` (`[Unreleased]`), `docs/REPORT.md` when a defect is found/fixed, `docs/PRD.md` when requirements/acceptance actually change. Commit and push immediately.


- **Always read the base code first.** Before any task, read carefully the code it touches *and* every code path related to it (bootstrap chain, helpers, autoloader, gateway, router/controller, route table, tests). Understand each line's logic and why it is written that way before deciding anything.
- Never rush an edit, never assume, never fabricate behaviour from memory. If something is unknown, verify in the repository (grep/view/run) instead of guessing; correct earlier claims when evidence contradicts them.

## 8. Drip Template Engine

Drip compiles `.html` templates to PHP under `storage/cache/views/` and renders the compiled file. Names are dot-notation without extension: `file`, `folder.file`. A leading capital letter means a module (`Module.file`, `Module.folder.file`); anything else is a folder, so view folders are lowercase.

- Echo: `{{$var}}` escapes via `System\Helper\Utilities::escape`; `{{!$var!}}` prints raw. Spaces are optional inside the braces.
- Never call `htmlspecialchars()`, `htmlentities()`, or `e()` inside `{{ }}` — the engine already escapes, so the second pass corrupts output. The compiler rejects it with a `already escapes` error.
- To build HTML by hand inside an expression, escape only the data and keep the markup in a raw block: `{{! '<b>'.htmlspecialchars($name, ENT_QUOTES, 'UTF-8').'</b>' !}}`.
- `{{-- … --}}` is a Drip comment (dropped at compile). A directive or echo written inside an HTML comment (`<!-- @yield('content') -->`) is unwrapped and compiled, so templates stay previewable.
- Unknown `@word` sequences are left untouched (CSS `@media`, `user@host`).
- `@section('content')` overrides the rendered child body for that slot; `@extend` and the third `render(..., $layout)` argument override accordingly.
- Compiled cache invalidates on the source file mtime; clear `storage/cache/views/` to force a recompile.
- Think about the target diff before writing: prefer rehearsing/verifying the idea; apply slowly and carefully, never reckless bulk rewrites.
- Preserve working behaviour: when simplifying/refactoring, keep a failing-before / passing-after record so an equivalent guarantee is shown, not assumed.
- If a safe change turns out to need more than expected, stop, restore state (e.g. `git stash`/checkout), and re-plan instead of piling edits.
- Verify with the project's real checks (§3/§7) and web probes after each incremental step, not only at the end.
