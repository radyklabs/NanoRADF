# MASTER PRE-ACTION CHECKLIST
## Mandatory Protocol for Every Code Change

**Version:** 1.0  
**Authority:** This checklist is MANDATORY. No exceptions. No shortcuts.  
**Enforcement:** Every task MUST complete this checklist before ANY edit/commit/push.

---

## PHASE 0: PRE-FLIGHT (Before ANY Analysis)

### 0.1 Context Verification
- [ ] **Read CLAUDE.md** — Full read, not skim. Note: role, stack, project overview, rules.
- [ ] **Read docs/RULES.md** — Full read. Note: runtime style, placement, bootstrap, security, git, verification.
- [ ] **Read docs/PLAN.md** — Current phase, decisions, open risks, parallelism map.
- [ ] **Read .claude/learnings.json** — All lessons, especially recent ones.
- [ ] **Check git status** — `git status --short`, `git diff HEAD` for working tree state.

### 0.2 Task Definition
- [ ] **Define task scope** in one sentence: "I will [action] to [outcome] because [reason]."
- [ ] **Identify all files** that will be touched (list them).
- [ ] **Identify dependencies** — what other files/systems will be affected?
- [ ] **Define success criteria** — how will I know it works? (test names, expected output)

---

## PHASE 1: DEEP ANALYSIS (Before ANY Edit)

### 1.1 File Reading (MANDATORY)
- [ ] **Read every target file** completely — not skim, not diff, FULL READ.
- [ ] **Never assume file content from memory or prior reads** — concurrent editors may have changed the file; always read fresh from disk before proposing any change.
- [ ] **Read all dependent files** — trace dependencies upstream and downstream.
- [ ] **Read config files** — understand current state before changing.
- [ ] **Read test files** — understand expected behavior.

### 1.2 Dependency Tracing
- [ ] **Trace config loading flow** — which file loads which, in what order?
- [ ] **Trace constant definitions** — where are constants defined, when available?
- [ ] **Trace function call chains** — who calls what, in what order?
- [ ] **Trace data flow** — config → service → renderer → output.

### 1.3 Impact Analysis
- [ ] **List all breaking changes** — API, config, behavior, tests.
- [ ] **List all side effects** — what else might break?
- [ ] **Identify rollback plan** — how to revert if broken?
- [ ] **Assess migration strategy** — if breaking, how to migrate safely?

### 1.4 Assumption Verification
- [ ] **Verify every assumption** with actual code, not memory.
- [ ] **Check constant availability** — is constant defined at usage point?
- [ ] **Check function existence** — is function defined before call?
- [ ] **Check file existence** — does file exist at expected path?

---

## PHASE 2: IMPLEMENTATION PLAN

### 2.1 Step-by-Step Plan
- [ ] Write numbered steps: 1, 2, 3... with exact file:line references.
- [ ] Each step must be atomic and verifiable.
- [ ] Define verification command for each step.

### 2.2 Risk Mitigation
- [ ] Identify highest risk step.
- [ ] Define rollback for each step.
- [ ] Define "stop criteria" — when to abort and ask user.

---

## PHASE 3: EXECUTION WITH VERIFICATION

### 3.1 Per-Step Execution
For EACH step:
- [ ] Make the change.
- [ ] Run verification command immediately.
- [ ] If FAIL → STOP → Revert → Analyze → Ask user if needed.
- [ ] If PASS → Document result → Next step.

### 3.2 Continuous Verification
- [ ] Run smoke test after every logical group of changes.
- [ ] Run full test suite before commit.
- [ ] Check git diff — only intended changes present?

---

## PHASE 4: PRE-COMMIT VALIDATION

### 4.1 Test Suite (ALL MUST PASS)
- [ ] `php tests/smoke_check.php` → PASS
- [ ] `php tests/assets_check.php` → PASS
- [ ] `php tests/view_check.php` → PASS
- [ ] `php tests/route_check.php` → PASS
- [ ] `php tests/layout_check.php` → PASS (if applicable)
- [ ] `php tests/bootstrap_probe.php` → PASS (if applicable)

### 4.2 Code Quality
- [ ] `git diff` — only intended changes, no accidental whitespace/formatting.
- [ ] No debug code left (`var_dump`, `echo`, `die`, `print_r`).
- [ ] No commented-out code blocks (unless documented why).
- [ ] All new code follows project style (4-space, LF, single quotes, etc.).

### 4.3 Documentation Sync
- [ ] Update `docs/CHANGELOG.md` under `[Unreleased]`.
- [ ] Update `docs/PLAN.md` if phase/status/decision changed.
- [ ] Update `docs/PRD.md` if requirements changed.
- [ ] Update `docs/REPORT.md` if defects found/fixed.
- [ ] Update `.claude/learnings.json` if new lesson learned.

---

## PHASE 5: COMMIT & PUSH

### 5.1 Commit
- [ ] Stage only intended files: `git add <specific-files>`
- [ ] Commit message follows convention: `type: description`
- [ ] No `Co-authored-by:` trailers.

### 5.2 Push
- [ ] `git push origin <branch>`
- [ ] Verify PR status if applicable.

---

## ENFORCEMENT RULES

### NEVER:
- ❌ Edit without reading file first.
- ❌ Assume constant/function/file exists — verify.
- ❌ Assume config state — trace the loading flow.
- ❌ Skip tests — ever.
- ❌ Commit without full test suite PASS.
- ❌ Push without verifying PR status.
- ❌ Use `git reset --hard` without verifying working tree.
- ❌ Edit multiple unrelated files in one commit.
- ❌ Make breaking changes without migration plan.
- ❌ Assume user wants something — ask if ambiguous.

### ALWAYS:
- ✅ Read before write.
- ✅ Trace before assume.
- ✅ Test before commit.
- ✅ Document before push.
- ✅ Ask if uncertain — better to ask than break.

---

## TASK-SPECIFIC CHECKLIST TEMPLATE

For each new task, create a derived checklist:

```markdown
# TASK: [One-line description]

## Scope
- Files to modify: [list]
- Dependencies: [list]
- Success criteria: [specific test names/outputs]

## Pre-Flight
- [ ] CLAUDE.md read
- [ ] RULES.md read
- [ ] PLAN.md read
- [ ] Learnings read
- [ ] Git status checked

## Analysis
- [ ] All target files read completely
- [ ] Dependencies traced
- [ ] Impact analyzed
- [ ] Assumptions verified

## Plan
1. [Step 1: file:line - action - verification]
2. [Step 2: file:line - action - verification]
...

## Execution
- [ ] Step 1 done → verified
- [ ] Step 2 done → verified
...

## Validation
- [ ] smoke_check PASS
- [ ] assets_check PASS
- [ ] view_check PASS
- [ ] route_check PASS
- [ ] git diff clean
- [ ] CHANGELOG updated
- [ ] PLAN.md updated (if needed)
- [ ] Learnings updated (if needed)

## Commit
- [ ] git add specific files
- [ ] git commit -m "type: description"
- [ ] git push
- [ ] PR status verified
```

---

## EMERGENCY STOP CONDITIONS

**STOP IMMEDIATELY AND ASK USER IF:**
- Any test fails unexpectedly.
- Git shows unexpected changes.
- Breaking change discovered mid-task.
- Constant/function not found at runtime.
- Uncertainty > 10% on any decision.
- User said "stop" or "wait".

---

**This checklist is LIVING DOCUMENT. Update it when new failure modes discovered.**

**Location:** `.claude/pre_action_checklist.md`  
**Usage:** Read this file at START of every session/task. Complete relevant sections before ANY action.