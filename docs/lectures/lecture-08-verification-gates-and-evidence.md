# Lecture 08: Verification Gates & Evidence

## "Looks Good To Me" is Not Evidence

In unharnessed agent sessions, the agent evaluates its own output through language generation:
> *"I have reviewed the code and confirmed that the canonical tag is properly configured."*

This is subjective hallucination. The agent cannot "see" PHP fatal errors or regex matching bugs through conversational reflection.

---

## Deterministic Verification Gates

The Genisoft Harness requires verifiable execution evidence produced by real runtime processes:

```text
               ┌───────────────────────────┐
               │    Modified PHP/JSON      │
               └─────────────┬─────────────┘
                             │
            ┌────────────────┴────────────────┐
            ▼                                 ▼
┌───────────────────────┐         ┌───────────────────────┐
│   verify-seo-safety   │         │schema-graph-validator │
│   - Insecure scheme   │         │ - Missing @type       │
│   - Unescaped meta    │         │ - Dangling @id        │
│   - Sitewide noindex  │         │ - Missing rich fields │
└───────────┬───────────┘         └───────────┬───────────┘
            │                                 │
            └────────────────┬────────────────┘
                             │
                             ▼
              [ EXIT CODE 0: VERIFIED PASS ]
                             │
                             ▼
              Evidence appended to progress.md
```

If the tool exits with non-zero status, the agent is blocked from committing and must self-correct.
