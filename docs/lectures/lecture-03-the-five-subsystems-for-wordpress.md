# Lecture 03: The 5 Subsystems for WordPress

To transform an unreliable AI agent into a deterministic software engineer on WordPress, we must construct **five interconnected subsystems**:

```
┌─────────────────────────────────────────────────────────────────┐
│                    THE 5 HARNESS SUBSYSTEMS                     │
│                                                                 │
│  ┌──────────────┐  ┌──────────────┐  ┌───────────────────────┐  │
│  │ Instructions │  │    State     │  │     Verification      │  │
│  │   AGENTS.md  │  │seo_tasks.json│  │ verify-seo-safety.php │  │
│  │   CLAUDE.md  │  │  progress.md │  │schema-graph-validator │  │
│  └──────┬───────┘  └──────┬───────┘  └───────────┬───────────┘  │
│         │                 │                      │              │
│         ▼                 ▼                      ▼              │
│  ┌──────────────┐  ┌─────────────────────────────────────────┐  │
│  │    Scope     │  │            Session Lifecycle            │  │
│  │ 1 Task / Turn│  │ init.sh ──► wp-seo-guard.php ──► Handoff│  │
│  │Definition of │  │         (Active Runtime Shield)         │  │
│  │     Done     │  │                                         │  │
│  └──────────────┘  └─────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
```

---

## 1. Instructions Subsystem (`AGENTS.md`)
- Defines what the agent is authorized to touch and what is strictly off-limits.
- Specifies WordPress Coding Standards (WPCS) and architectural invariants.
- Uses **Progressive Disclosure**: High-level routing in the root instruction file, delegating specialized specifications to dedicated docs.

## 2. State Subsystem (`seo_tasks.json` & `progress.md`)
- Maintains machine-readable and human-readable state on disk.
- Lets session $N+1$ resume where session $N$ stopped, from what is written on disk rather than from memory.
- Records concrete verification command output as proof of completion.

## 3. Verification Subsystem (`tools/`)
- Pure evidence-based gating: **No proof = Not done**.
- CLI tools run deterministically before any commit.
- Flags syntax errors, insecure canonicals, and dangling Schema entities.

## 4. Scope Subsystem
- Constrains the agent to **exactly ONE atomic feature per session**.
- Imposes an unyielding Definition of Done (DoD).
- Prevents the agent from rewriting the backlog to hide unfinished work.

## 5. Lifecycle Subsystem (`init.sh`, `wp-seo-guard.php`, `session-handoff.md`)
- Bootstraps the environment cleanly at session start.
- Intercepts dangerous runtime operations live via a Must-Use plugin.
- Leaves a pristine Git status and clear handoff note for the next engineer.
