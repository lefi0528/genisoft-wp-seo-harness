# Lecture 07: Scope Control & Atomic Tasks

## The Overreach Trap

When given broad authority, coding agents suffer from **scope creep**:
- Asked to add a meta description, the agent refactors the entire theme header.
- Asked to implement a Breadcrumb schema, it rewrites the site navigation menu.
- Half-finishes three different features, leaving all three unverified.

---

## The One-Task Invariant

The Genisoft Harness enforces a strict operational rule:
> **One agent session = Exactly ONE task from `seo_tasks.json`.**

### Definition of Done (DoD)
An atomic task cannot be closed until all four criteria are fulfilled:
1. `php -l` exits code 0 with zero syntax errors.
2. `php tools/verify-seo-safety.php` exits code 0 with zero critical violations.
3. Relevant Schema entities pass `node tools/schema-graph-validator.js`.
4. Evidence string is recorded in both `seo_tasks.json` and `progress.md`.
