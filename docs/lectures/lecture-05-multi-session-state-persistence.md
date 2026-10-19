# Lecture 05: Multi-Session State Persistence

## Why Agents Suffer from Amnesia

In real software development, complex technical SEO sprints cannot be solved in a single prompt or single session. They require:
- Phase 1: Baseline Audit & Safety Deployment.
- Phase 2: Schema.org Graph Architecture.
- Phase 3: Robots & Canonical Normalization.
- Phase 4: Core Web Vitals Optimization.
- Phase 5: Verification & Testing.

When you start a new conversation with an agent, its context window is empty. It has zero memory of what was done previously.

---

## The Dual-File State Pattern

The Genisoft Harness solves this through two files persisted to the local filesystem:

### 1. `seo_tasks.json` (Machine-Readable Backlog)
```json
{
  "id": "SEO-02",
  "phase": "Schema.org Architecture",
  "title": "Implement Connected Knowledge Graph (@graph)",
  "status": "completed",
  "verification_gate": "node tools/schema-graph-validator.js build/schema-home.json",
  "evidence": "3/3 entities validated with 0 dangling @id references."
}
```

### 2. `progress.md` (Human/Agent-Readable Session Log)
- Records what was changed in each session.
- Records files modified and git commit hashes.
- Designates the exact task ID to be picked up in the next session.
