# Project 04: Full Site Audit & Certification

## Objective
Execute the full 100-point repository audit script and achieve production-grade certification. Each point is earned by a working artifact: the audit runs the safety scanner and the schema validator against known bugs, so placeholder files score low.

---

## Running the Audit
```bash
bash tools/audit-harness.sh
```

### Certification Scorecard
```text
Checking Subsystem 1: Instructions (AGENTS.md / CLAUDE.md)... PASS (+20 pts)
Checking Subsystem 2: State Persistence (seo_tasks.json & progress.md)... PASS (+20 pts)
Checking Subsystem 3: Verification (verify-seo-safety.php / schema-validator)... PASS (+20 pts)
Checking Subsystem 4: Scope Control (Atomic task backlog & DoD)... PASS (+20 pts)
Checking Subsystem 5: Session Lifecycle (init.sh, session-handoff.md, wp-seo-guard.php)... PASS (+20 pts)

--------------------------------------------------
FINAL HARNESS SCORE: 100 / 100
--------------------------------------------------
✔ Production-Grade Harness. Certified for Autonomous Agent Operations.
```
