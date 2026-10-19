# Genisoft WP SEO Harness — Technical Architecture

## 1. System Philosophy: Deterministic Verification vs. Stochastic LLMs

Large Language Models (LLMs) are probabilistic systems. When tasked with modifying WordPress themes, hooks, and SEO metadata, an unharnessed agent frequently:
1. Emits conflicting canonical tags.
2. Unintentionally injects `noindex` directives across paginated archives or category landing pages.
3. Produces broken or fragmented Schema.org JSON-LD snippets.
4. Leaves unescaped dynamic parameters that introduce XSS vulnerabilities into `<meta>` tags.

The **Genisoft WordPress SEO Harness** wraps the AI agent in a deterministic, five-layered supervisory envelope:

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                      GENISOFT HARNESS GOVERNANCE ENGINE                     │
│                                                                             │
│  ┌───────────────────┐    ┌────────────────────┐    ┌────────────────────┐  │
│  │ 1. INSTRUCTIONS   │    │ 2. STATE PERSIST   │    │ 3. VERIFICATION    │  │
│  │    AGENTS.md      │    │    seo_tasks.json  │    │    Safety Scanner  │  │
│  │    CLAUDE.md      │    │    progress.md     │    │    Schema Validator│  │
│  └─────────┬─────────┘    └──────────┬─────────┘    └──────────┬─────────┘  │
│            │                         │                         │            │
│            ▼                         ▼                         ▼            │
│  ┌───────────────────┐    ┌──────────────────────────────────────────────┐  │
│  │ 4. SCOPE BOUNDS   │    │ 5. LIFECYCLE & RUNTIME GUARD                 │  │
│  │    1 Task / Turn  │    │    init.sh ──► wp-seo-guard.php ──► Handoff  │  │
│  │    Strict DoD     │    │    Intercepts catastrophic failures at runtime│  │
│  └───────────────────┘    └──────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## 2. The 5 Subsystems in Detail

### Subsystem 1: Instructions (`templates/AGENTS.md`)
- Defines explicit non-negotiable invariants: no parent theme edits, no non-HTTPS canonicals, mandatory escaping.
- Uses **Progressive Disclosure**: High-level routing in root `AGENTS.md`, task-specific specs in `docs/SEO_RULES_SPEC.md`.

### Subsystem 2: State Persistence (`templates/seo_tasks.json` & `progress.md`)
- LLMs suffer from context window degradation and amnesia between sessions.
- State is serialized directly to disk in `seo_tasks.json` (machine-readable) and `progress.md` (human/agent readable log).
- Each task stores verifiable execution evidence (CLI output string).

### Subsystem 3: Verification Gates (`tools/verify-seo-safety.php` & `tools/schema-graph-validator.js`)
- Static analysis of touched files before commit.
- Schema validator checks entity cross-references (`@id`) to ensure an interconnected Google Knowledge Graph rather than disjointed micro-data.

### Subsystem 4: Scope Governance
- Limits agent execution to a single atomic SEO feature per turn.
- The agent is forbidden from rewriting the backlog or declaring victory until all verification tests pass.

### Subsystem 5: Session Lifecycle & Runtime Guard (`tools/wp-seo-guard.php`)
- A zero-overhead Must-Use Plugin (`mu-plugin`) that intercepts dangerous runtime operations:
  - Strips any accidental `noindex` on the homepage at filter priority `9999`.
  - Normalizes trailing slashes and forces HTTPS on canonical URLs.
  - Prevents recursive redirect loops in `template_redirect`.
