---
name: genisoft-wp-seo-harness
description: Scaffold, audit, and enforce the Genisoft WordPress Technical SEO Harness. Use when working on WordPress SEO tasks to prevent accidental de-indexing, canonical loops, schema errors, and PHP crashes during AI agent operations.
license: MIT
---

# Genisoft WordPress SEO Harness Skill

Use this skill whenever an AI coding agent is tasked with technical SEO, schema implementation, canonical restructuring, or Core Web Vitals optimization on a WordPress / WooCommerce repository.

## 5 Subsystems Enforced:
1. **Instructions**: `templates/AGENTS.md` operating rules.
2. **State**: `templates/seo_tasks.json` & `templates/progress.md`.
3. **Verification**: `tools/verify-seo-safety.php` & `tools/schema-graph-validator.js`.
4. **Scope**: One atomic task at a time, strict Definition of Done.
5. **Lifecycle**: `init.sh` startup, `wp-seo-guard.php` runtime, clean session handoff.

## Workflow:
1. Inspect repo with `bash tools/audit-harness.sh`.
2. Ensure `wp-seo-guard.php` is active in `wp-content/mu-plugins/`.
3. Pick 1 task from `seo_tasks.json`.
4. Run verification gates before declaring done.
5. Update state and write session handoff note.
