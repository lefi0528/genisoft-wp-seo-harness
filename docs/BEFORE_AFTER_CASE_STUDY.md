# Case Study: Unharnessed Agent vs. Genisoft WP SEO Harness

### Scenario
An e-commerce WordPress store requested an automated SEO sprint to implement product structured data, optimize meta tags, and configure robots directives.

---

## Scenario A: Unharnessed Agent (Prompt-Only)

1. **Session 1**:
   - The agent edited the parent theme's `functions.php` directly.
   - Introduced a custom `wp_robots` filter. Due to an inverted conditional statement (`if (!is_single())`), it set `noindex = true` on the homepage and category archives.
   - Output inline Microdata instead of unified JSON-LD `@graph`.
2. **Session 2**:
   - The agent lost context of what had been edited.
   - Declared "All SEO tags implemented successfully!"
3. **Outcome**:
   - Google de-indexed the store's homepage and 12 category pages within 48 hours.
   - Client suffered a catastrophic 72% organic traffic drop.

---

## Scenario B: Governed by Genisoft WP SEO Harness

1. **Session 1 (Bootstrap & Safety)**:
   - Agent ran `bash init.sh` and deployed `tools/wp-seo-guard.php` to `mu-plugins/`.
   - Picked task `SEO-01` from `seo_tasks.json`.
2. **Session 2 (Implementation & Verification)**:
   - Agent attempted to write the robots filter.
   - Ran `php tools/verify-seo-safety.php`.
   - **Result**: The Harness scanner immediately flagged:
     ```
     [FAIL] Unconditional 'noindex' detected in wp_robots filter. Risk of de-indexing entire site.
     ❌ Safety verification FAILED. Aborting agent session to prevent production damage.
     ```
   - Agent immediately reverted the faulty condition and re-tested.
   - Verification passed.
3. **Session 3 (Schema Graph)**:
   - Agent implemented JSON-LD knowledge graph.
   - Ran `node tools/schema-graph-validator.js output.json`.
   - Validator verified 0 dangling `@id` references and complete Google Rich Result compliance.
4. **Outcome**:
   - **Zero regressions, 100% test pass rate, perfect Rich Results eligibility**.
   - Client saw a 24% increase in search visibility with rich snippets within 14 days.
