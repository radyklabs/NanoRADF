# CLAUDE.md — NanoRADF Frontend Project Instructions

## Role

Senior PHP & Headless-Frontend Engineer. Tim frontend PHP elit dan kelas dunia yang membangun framework yang minimal, kecil, ringan, dan cepat yang memiliki fungsi (2 mode) sebagai frontend (PHP + HTML + CSS + JS) dan backend (headless PHP + API) yang bisa saling komuniikasi via HTTP server-to-server, bukan browser fetch. Pengembang-guna framework ini bisa menentukan mode mana yang akan digunakan sebelum mulai pengembangan aplikasi, apakah akan jadi headless, frontend yang berkomunikasi dengan headless php, atau mau full package.

## Stack

- PHP 8.1+ (`declare(strict_types=1)` di setiap file).
- Zero runtime dependencies. Tidak ada Composer pada runtime (custom PSR-4 autoloader; `composer.json` opsional dev-time only).
- Server-side rendering: PHP render HTML, bukan SPA JS yang memanggil API dari browser.
- Komunikasi frontend → backend: PHP HTTP client (cURL / stream context) server-to-server.
- HTML5 semantik, CSS (tanpa framework CSS paksaan), vanilla JS hanya untuk enhancements progresif.
- Token auth disimpan server-side di `$_SESSION` (PHP session, cookie `HttpOnly`, `SameSite=Strict`, `secure` di prod). **Tidak ada token di localStorage / client-side storage.**

## Project Overview

### Dev URL (wajib)

- **App:** `http://nanoradfw.local` — docroot = `public/` (front controller `public/index.php` via `public/.htaccess`).
- **PHP:** 8.3.36 (LocalWP): `C:\Users\Rahen\AppData\Roaming\Local\lightning-services\php-8.3.36+0\bin\win64\php.exe`.
- **MySQL:** `127.0.0.1:10019` (LocalWP). `sql/local.sql` is a leftover local dump — gitignored, not a project asset.
- CLI smoke checks: bootstrap from any CWD with `require 'public/nanoradf.php'` (see `docs/RULES.md`).

## Project Rules

1. Baca `docs/RULES.md` sebelum bekerja. Ikuti konvensi.
2. File yang harus sinkron: `docs/PLAN.md`, `docs/PRD.md`, `docs/REPORT.md`, `docs/CHANGELOG.md`.
3. Tidak ada `eval()`, `exec()`, `shell_exec()`, `system()`, `proc_open()`, `unserialize()` pada input tidak terpercaya. Prepared statements-only jika frontend menulis DB (v1: tidak, frontend hanya baca API backend).
4. Setiap input dari backend divalidasi dan di-escape saat output (XSS via `htmlspecialchars`).
5. Secrets hanya di `.env` (tidak di komit). JWT secret tinggal backend; frontend hanya menyimpan access/refresh token di session.
6. Satu tugas satu perubahan. Tidak ada drive-by refactor.
7. Format dengan formatter repo (jika ada), tidak secara manual.

Always read `docs/RULES.md` before working on this repository and follow its conventions.
Local environment configuration lives in `.env` (gitignored, never commit). This project does not use `docs/CONFIG.md` / `docs/CONFIG.sample.md`; do not create them.

**Files tracked for sync:** `docs/PLAN.md`, `docs/PRD.md`, `docs/REPORT.md`, `docs/CHANGELOG.md`.

## Debugging Principle (non-negotiable)

- **Fixing ≠ trimming.** Never delete or stub out problematic lines to make a bug disappear. Deleting the trigger hides the defect and guarantees it returns.
- Root cause only: find why the line/feature fails, fix the actual defect, and confirm with a failing-before / passing-after check.
- Never simplify a template to a trivial case to dodge a compiler bug; protect or fix the compiler instead.

## Scope and User-Change Protection (non-negotiable)

- Before editing, deleting, moving, formatting, or otherwise changing files, inspect the working-tree changes and distinguish existing user changes from changes made for the current task.
- Never overwrite, revert, delete, move, or otherwise alter pre-existing user changes. If a file has changes outside the explicitly requested scope, do not touch it without the user's explicit confirmation. If the requested change overlaps existing edits or it is unclear whether an edit would affect them, stop and ask first.
- Keep work focused on the requested task and files that evidence shows are implicated. Do not assume other files or code are faulty; verify the specific cause from relevant code, errors, or tests before expanding the investigation or change scope.
- Expand the scope only when concrete evidence shows it is necessary. Explain the evidence and get the user's confirmation before modifying files outside the task scope.

## Decision Framework

For logic or major changes:
1. Verify the current code state by reading the actual files.
2. Cross-reference the four project documents.
3. If a decision is needed, ask the user to choose between clear options before proceeding.
4. Record the decision in PLAN, REPORT, and CHANGELOG.
5. Do not execute code or implement the change until the user confirms.

## Versioning & Release Process

Use Semantic Versioning (MAJOR.MINOR.PATCH):
- **MAJOR** — breaking changes (API, config structure, DB schema)
- **MINOR** — new backward-compatible features
- **PATCH** — bug fixes, security patches, documentation-only changes

Keep changes under `[Unreleased]` during normal development. Promote it only when the release scope is agreed and frozen, changes are complete and validated, and the release version/date are known. Determine the version from all changes since the latest release, verifying impact against the actual code, docs, tests, and release history.

When a planned phase is complete and merged into `dev`, review release readiness and report the recommended version and blockers. Do not promote `[Unreleased]`, update the plugin version, tag, or publish unless the release is ready and the user invokes `>release`.

Release workflow (`>release`) prepares, merges, and tags in one invocation; never tag a commit on `dev`:
1. Require a clean, current `dev` branch; verify release readiness, tests/CI, version history, and that `vX.Y.Z` does not exist. Review the complete `dev` → `main` diff and stop if it includes unrelated or unintended changes. Move `[Unreleased]` entries to `## [X.Y.Z] - YYYY-MM-DD`, add a fresh empty `[Unreleased]` section above it, and update the plugin `Version:` header.
2. Commit as `chore(release): X.Y.Z`, push `dev`, and create a PR from `dev` to `main` titled exactly `chore(release): X.Y.Z`.
3. If the PR is mergeable and GitHub rules allow it, merge it with `gh pr merge --merge`; do not bypass rules or wait for a separate manual merge. If a required approval, check, or other rule blocks the merge, stop and report the blocker.
4. Wait for `.github/workflows/release-tag.yml` to validate the merged PR and create/push the annotated `vX.Y.Z` tag. Verify the tag points to the exact merge commit before reporting success. The workflow must already be present on `main`, and GitHub Actions must have `contents: write` permission with tag rules permitting its `GITHUB_TOKEN` to create `v*` tags. Optionally publish a GitHub Release from the verified tag.
5. If tagging fails, do not repeat the release or create a local tag. Fix the cause and use the workflow's manual recovery input with the verified release merge SHA; it independently verifies the merged release PR and metadata before tagging.

## Commit & Push Rule

- Commit completed changes immediately; never leave changes uncommitted or commits unpushed.
- Push to the current branch on `origin`.
- Before merging to `dev`, update the branch against `dev` and create a PR.
- Merge using a squash or merge commit, then delete the merged branch.
- **Never include `Co-authored-by:` trailers in any commit** — this applies to all sources (Copilot, Claude, Codex, other AI tools, humans) and all commit types (squash, merge, rebase, amend). If a tool adds such a trailer automatically, strip it before committing/pushing.

## Custom Commands

| Command | Action |
|---------|--------|
| `>branch <description>` | Choose a concise prefixed kebab-case branch name, verify it is unused, then create and switch to it. |
| `>release` | One invocation prepares changelog/version, opens and merges a `chore(release): X.Y.Z` PR to `main` when rules permit, then waits for `release-tag.yml` to validate and create/push the tag. |
| `>push` | Inspect status and diff, add a per-file summary under `[Unreleased]` in the changelog, then stage, commit, and push the current branch. |
| `>sync` | Run `>push`, update against `dev`, create and merge a PR into `dev`, then delete the merged branch. |
| `>track` | Compare the plan against the code, update statuses and related docs, then commit and push. |
| `>audit` | Deep audit: manual file-by-file, line-by-line review of logic, cross-file consistency/correlation, dead/junk code, security gaps, and anomalies; tools are evidence only; record findings in `docs/REPORT.md` and related docs. |

### `>audit`

Perform a deep repository audit and document findings. PHPCS, PHPStan, PHPUnit, and other tools are **supporting evidence only** — a green tool run is never the audit itself, and tool output must never substitute for reading the code. Do not silently fix or remove code during the audit.

1. **Frame the audit.** Inspect `git status`, the current branch, and the commit being audited before reading or editing files. Preserve all pre-existing changes; if requested scope overlaps an existing edit, stop and ask. Inventory tracked files (plus relevant ignored files) with line counts; plan the coverage so every in-scope text file is read end to end.
2. **Line-by-line manual review (mandatory, the core of the audit).** Read every in-scope file completely — source, tests, configuration, workflows, and project documentation. For each file, keep a coverage ledger entry: reviewed / partially reviewed (what was missed and why) / excluded (why). For binaries, inspect type, size, and relevant references instead of treating them as text. Exclude generated, vendored, cache, and build artifacts unless evidence shows they affect runtime behavior or expose a concrete risk.
3. **Logic and control flow.** Trace actual execution paths per entry point (routing hook → middleware → controller → service/model → template/output), including error paths, early returns, boundary conditions, and state mutations. Flag logic that cannot work as written, branches that contradict their own guards, and behavior that differs between WP runtime and pure-PHP test context.
4. **Cross-file consistency and correlation.** Correlate definitions with consumption across the repo: config keys defined ↔ read; Registry keys set ↔ `Registry::get()`; helpers/functions/classes defined ↔ called; constants defined ↔ used (per runtime context); interfaces ↔ implementations; tests ↔ the behavior they claim to cover; documentation/plan/changelog claims ↔ actual code. Record every mismatch (defined-but-unused, used-but-never-defined, claimed-but-not-covered, divergent assumptions between files).
5. **Dead code, junk code, unnecessary work.** Search for unreachable code, unused functions/classes/methods/config keys/directives/test helpers, duplicated logic, leftover debug/scratch code, commented-out blocks, and per-request redundant work (repeated scans, stat/hash churn, unbounded growth). Check reachability (call sites, hooks, reflection, string-based registration such as directive/handler names) before labeling anything dead; state the search evidence. Note intentional trade-offs marked `ponytail:` as such.
6. **Security gaps.** Walk every trust boundary: input (request/query/POST/file/CLI), routing and skip paths, authn/authz (nonce + `current_user_can` on admin actions), output escaping per context, SQL parameterization, path traversal/file include and symlink guards, template compilation/raw output, cache/filesystem writes, secrets/credentials, error messages and information disclosure, dependency and workflow (CI) supply chain. For each boundary, state either the guard found (file:line, verified correct) or the gap (finding).
7. **Anomalies.** Note inconsistencies that hint at deeper defects: divergent error-handling styles, mixed separator/constant usage, naming or namespace drift, format mismatches between paired files (e.g. `.pot` vs `.po`), stale comments that no longer match the code, and version/URL skew between files.
8. **Tools as evidence.** Run the existing static/lint/test tooling and record exact commands and results (including environment failures and version/limit quirks). Never execute an exploit, alter production data, or expose secrets. Report tool/environment failures explicitly; never describe an incomplete check as a pass. When the tools and the manual read disagree, investigate and record the resolution.
9. **Classify findings.** Mark each finding CONFIRMED (with file:line evidence and a shown trace) or HYPOTHESIS (with what remains to verify) or INTENTIONAL TRADE-OFF (with the marker that shows it). Severity: critical/high/medium/low/info. No finding without precise file and line evidence.
10. **Report.** Add a dated audit entry to `docs/REPORT.md` with the branch and commit, scope and exclusions, the full file-coverage ledger, tools and exact results, and the findings (severity, file:line, what, root cause, impact, confidence, concrete remediation and verification plan). If no findings are confirmed, say so only for the scope actually reviewed and never as a blanket statement.
11. **Follow-through.** Update `docs/PLAN.md` to track agreed remediation and status, and add an entry under `[Unreleased]` in `docs/CHANGELOG.md` for the audit/documentation work. Update `docs/PRD.md` only when product requirements or acceptance criteria actually change. Keep all findings and recommendations in the report; do not claim a fix until it is implemented and verified.

## Modular Architecture (mirip BoneRADF backend)

Two layers, strictly separated:

- `src/` — framework core: kernel (`.config` → `.structures` → `Boot`), base controllers, services, helper, contracts, view taxonomy, themes. Never contains application logic.
- `apps/` — application layer:
  - `apps/Config/Routes.php` — central route table (URL → handler), single source of truth.
  - `apps/Services/` — app-wide shared services.
  - `apps/Modules/<Name>/` — self-contained module, folder contract:
    - `Manifest.json` — module manifest (identity, enabled flag, registration metadata).
    - `Config/` — module configuration classes.
    - `Controllers/` — HTTP controllers.
    - `Model/` — data access. v1 is read-only against the headless API.
    - `Services/` — module business logic.
    - `Views/` — module view classes.

Namespace contract enforced by `System\Kernel\Boot::autoloader` (prefixes from global constants `nssys` / `nsappmod`, path constants from `.structures`):

- `System\...` → `src/...`
- `App\Modules\<Name>\...` → `apps/Modules/<Name>/...`

Bootstrap order: `public/index.php` → `public/nanoradf.php` (defines `basepath`/`publicpath`/`sep`) → `.config` (loads `.supports` helpers, then `.structures` path+namespace constants, then `src/Kernel/Boot.php`) → `Boot::initiate()` registers the autoloader. Dotfiles are fragile — change them only with a boot-level smoke check (see `docs/RULES.md`).

## Blade-shaped View System

Views are plain HTML composed server-side (include/parameter composition), shaped like Blade partials — no client-side templating, no JS framework. Taxonomy under `src/Views/`, one folder per role:

- `Components/` — reusable blocks with state/variants (e.g. `Alerts.html`).
- `Elements/` — small primitive fragments (e.g. `Badge.html`).
- `Layouts/` — full-page shells (e.g. `Login.html`).
- `Patterns/` — recurring page sections (e.g. `Header.html`).
- `Errors/` — error pages (e.g. `404.html`).
- `Scripts/` — progressive-enhancement JS bound to views (e.g. `Dropdown.js`).
- `Tokens/` — design tokens as CSS (e.g. `System.css`).

Module views live in `apps/Modules/<Name>/Views/` as PHP view classes (e.g. `WelcomeView.php`). Public theme assets live in `public/themes/<theme>/`, admin assets in `src/Themes/admin/`.

**Status:** taxonomy folders exist; the renderer/composer is planned, not implemented (`docs/PLAN.md` Phase 2). Never describe pending behavior as done.

## Directory Layout

```text
.
├── .config              # bootstrap #2: helpers → structures → Boot   [code]
├── .structures          # path + namespace constants (is_dir guarded) [code]
├── .supports            # global helper library (is/interlace/__env…) [code]
├── .env                 # environment config (gitignored)             [config]
├── .envrc               # LocalWP direnv (gitignored)                 [config]
├── .gitignore
├── public/              # web root — only public entry
│   ├── index.php        # entry #1: sets folder names, calls gateway  [code]
│   ├── nanoradf.php      # gateway: basepath, .config, Boot, errors    [code]
│   ├── .htaccess         # front-controller rewrite                    [config]
│   └── themes/default/   # public theme assets                         [scaffold]
├── src/                 # framework core (namespace System\…)
│   ├── Kernel/          # Boot.php [code]; Router/Controller [scaffold]
│   ├── Controllers/      # Api/Private/Public base controllers         [scaffold]
│   ├── Services/         # AssetsService etc.                          [scaffold]
│   ├── Helper/           # Utilities                                   [scaffold]
│   ├── Contracts/        # Interfaces + Traits (Orchestrator)          [scaffold]
│   ├── Views/            # Blade-shaped taxonomy (7 role folders)      [scaffold]
│   └── Themes/admin/     # admin theme assets                          [scaffold]
├── apps/                # application layer (namespace App\…)
│   ├── Config/Routes.php # route table                                 [scaffold]
│   ├── Services/         # shared app services                         [empty]
│   └── Modules/Welcome/  # reference module (Manifest/Config/Ctrl/…)    [scaffold]
├── tests/               # smoke checks & test scripts (D7)           [code]
├── docs/                # RULES, PLAN, PRD, REPORT, CHANGELOG
├── sql/local.sql        # leftover local DB dump (gitignored)
└── CLAUDE.md / README.md / LICENSE
```

Legend: `[code]` runs today · `[scaffold]` exists as 0-byte placeholder · `[config]`/`[empty]` non-code.

## File Boundary (non-negotiable)

- `public/` — the only web-accessible directory; single front controller. No business logic, no secrets, no direct class definitions beyond the entry/gateway.
- `src/` — framework core only. App-specific behavior never goes here; extend via module code or contracts/traits in `src/Contracts/`.
- `apps/` — application code only. Never fork/copy core classes into modules; extend them.
- `tests/` — all test and smoke-check files (decision D7). Never place test files in `public/` or `src/`.
- `docs/` — project documents only (`RULES`, `PLAN`, `PRD`, `REPORT`, `CHANGELOG`).
- `sql/` — local database artifacts only; contents gitignored.
- `.supports` / `.config` / `.structures` — root bootstrap dotfiles (PHP). Fragile: any edit requires the boot smoke check to pass before commit.
- One class per file, path must match the PSR-4 mapping enforced by `Boot::autoloader` (`System\…` → `src/`, `App\Modules\<Name>\…` → `apps/Modules/<Name>/`).
- No new top-level directory, no renamed scaffold folder without recording the decision in `docs/PLAN.md` + `docs/CHANGELOG.md`.

## Secure Coding Rules (injection-hack-free contract)

1. `declare (strict_types=1)` in every PHP file. Forbidden on any untrusted input: `eval()`, `exec()`, `shell_exec()`, `system()`, `proc_open()`, `popen()`, `unserialize()`, `create_function()`, variable variables from input, dynamic `include`/`require` of user-controlled paths.
2. Escape every output per context: `htmlspecialchars ($value, ENT_QUOTES, 'UTF-8')` (via `src/Helper/Utilities` once implemented). Raw output only for vetted, non-user HTML.
3. Database writes (when introduced): prepared statements only — no string-concatenated SQL. v1 frontend is read-only against the backend API and does not write the DB.
4. Frontend → backend calls are server-to-server (PHP cURL / stream context), never browser fetch. Validate HTTP status and response shape before rendering any backend value.
5. Secrets live only in `.env` (gitignored). JWT secret stays backend-side. Frontend tokens live only in `$_SESSION` (cookie: `HttpOnly`, `SameSite=Strict`, `secure` in prod) — never in `localStorage`/JS-readable storage.
6. Validate and type-check all route params and request input; reject unknown keys; never trust backend response fields without validation + escaping (rule 2).
7. State-changing endpoints require authentication plus CSRF defense (session-bound nonce) before execution.
8. Error disclosure is gated by `.env`: generic 500 body in prod, details only via `error_log`; message/file/line shown only when `app.env` ∈ {local, dev} and `app.debug=true` (see `public/nanoradf.php`).

## Branching Model

- `main` — release-ready only; tidak commit WIP langsung.
- `dev` — working branch; feature work merge ke sini.
- `feat/<slug>`, `fix/<slug>`, `hotfix/<slug>`, `patch/<slug>` dari `dev`.
- Conventional commits: `feat:`, `fix:`, `docs:`, `refactor:`, `test:`, `chore:`, `security:`. Tanpa `Co-authored-by:`.

## Status Legend

- ✅ Done | ⚠️ Open | 🔵 Info | 🟠 Major | 🔴 Critical

## Precedence

Project file ini mengatur pekerjaan frontend di repo ini. Global `CLAUDE.md` (no hallucination, surgical changes, docs sync) tetap berlaku. Saat konflik, file ini menang untuk keputusan spesifik frontend; global rules selalu menang untuk disiplin universal.
