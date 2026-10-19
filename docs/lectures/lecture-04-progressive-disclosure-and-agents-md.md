# Lecture 04: Progressive Disclosure & AGENTS.md

## The Anti-Pattern: The Monolithic System Prompt

Many developers try to control coding agents by dumping 2,000 lines of guidelines into a single prompt:
- All WordPress coding rules.
- Complete Schema.org specifications.
- Core Web Vitals optimization guides.
- MySQL database optimization rules.

### Why This Fails
1. **Context Window Contamination**: Massive prompts consume 15,000+ tokens before the agent reads a single line of your codebase.
2. **Attention Dilution**: Transformer self-attention distributes weights across irrelevant rules, causing the agent to ignore critical safety invariants.
3. **Instruction Drift**: As the conversation progresses, the agent forgets early prompt sections.

---

## The Solution: Progressive Disclosure

**Progressive Disclosure** means providing the agent with a lean root file that acts as an index, pointing to specialized reference files on demand:

```text
REPOSITORY ROOT
├── AGENTS.md               <-- The Operating Index (Invariants & Routing)
├── docs/
│   ├── SEO_RULES_SPEC.md   <-- Loaded ONLY when authoring SEO filters
│   └── ARCHITECTURE.md     <-- Loaded ONLY when refactoring structure
└── templates/
    └── seo_tasks.json      <-- Read to discover the active task
```

### The Rule of Thumb
- `AGENTS.md` should be **under 150 lines**.
- It tells the agent **who it is, what it must NEVER do, and where to find detailed specifications**.
