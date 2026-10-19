# Hands-On Projects Overview

The Genisoft WP SEO Harness curriculum includes **4 hands-on projects**. Each project allows you to observe before/after comparisons between an unharnessed agent and an agent governed by the Genisoft Harness.

```
┌──────────────────────────────────────────────────────────────────┐
│                       PROJECTS PROGRESSION                       │
│                                                                  │
│  Project 01: Prompt-Only vs. Minimal Harness                     │
│  ──► Observe the catastrophic noindex failure mode               │
│                                                                  │
│  Project 02: Schema Knowledge Graph Repair                       │
│  ──► Convert broken microdata into validated JSON-LD @graph      │
│                                                                  │
│  Project 03: Runtime Safety Guard Deployment                     │
│  ──► Install and test active interception via mu-plugins         │
│                                                                  │
│  Project 04: Full Site Audit & Certification                     │
│  ──► Run the complete 100-point audit on a WordPress repository  │
└──────────────────────────────────────────────────────────────────┘
```

---

## Directory of Projects

| Project | Target Skill | Deliverable |
|---|---|---|
| **[Project 01](./project-01-prompt-only-vs-harnessed.md)** | Baseline Failure vs. Guarded Execution | Experience the fatal `wp_robots` inverted condition and fix it using the verification scanner. |
| **[Project 02](./project-02-schema-knowledge-graph-repair.md)** | Knowledge Graph Architecture | Repair a disconnected JSON-LD graph into a connected `@graph` that the validator accepts. |
| **[Project 03](./project-03-runtime-safety-guard-deployment.md)** | Active Defense & Interception | Deploy `wp-seo-guard.php` to neutralize live redirect loops and emergency noindex events. |
| **[Project 04](./project-04-full-site-audit-and-certification.md)** | Capstone Certification | Run `audit-harness.sh`, which executes the harness tools against known bugs, and reach 100/100 on your repository. |
