# Claude Code / Codex Fast-Guide — Genisoft WP SEO Harness

### Key Verification Commands
```bash
# Run full harness test suite
npm test

# Run Schema.org validator
node tools/schema-graph-validator.js <json-or-html-file>

# Run SEO safety gate scanner
php tools/verify-seo-safety.php <directory-or-file>

# Audit repository harness score
bash tools/audit-harness.sh
```

### Code Style Invariants
- Enforce strict WordPress Coding Standards (WPCS).
- Never modify core or parent themes.
- Escape all HTML meta outputs with `esc_attr()`.
- Use Schema.org `@graph` format with `@id` references.
- Always check `progress.md` before starting and update it before stopping.
