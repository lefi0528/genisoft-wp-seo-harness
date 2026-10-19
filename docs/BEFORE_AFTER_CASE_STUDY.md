# Illustrative Scenario: Unharnessed Agent vs. Genisoft WP SEO Harness

> **Note:** This is a constructed teaching scenario, not a client engagement. It shows how the failure modes from Lecture 02 play out and where each harness gate stops them. The scanner output below is real: reproduce it with `projects/project-01-baseline-vs-harnessed/starter/`.

### Scenario
An e-commerce WordPress store asks an AI coding agent for an SEO sprint: product structured data, meta tags, and robots directives.

---

## Scenario A: Unharnessed Agent (Prompt-Only)

1. **Session 1**:
   - The agent edits the parent theme's `functions.php` directly.
   - It adds a custom `wp_robots` filter with an inverted condition (`if (!is_single())`), which sets `noindex` on the homepage, pages and category archives.
   - It outputs inline Microdata instead of a connected JSON-LD `@graph`.
2. **Session 2**:
   - The agent has lost track of what it edited in session 1.
   - It declares "All SEO tags implemented successfully!"
3. **Likely outcome**:
   - As Google recrawls the store, the homepage and category pages drop out of the index, taking their organic traffic with them until the filter is fixed and the pages are recrawled.

---

## Scenario B: Governed by Genisoft WP SEO Harness

1. **Session 1 (Bootstrap & Safety, task `SEO-01`)**:
   - The agent runs `bash init.sh` and deploys `tools/wp-seo-guard.php` to `mu-plugins/`.
   - Gate: `php tools/verify-seo-safety.php tools/wp-seo-guard.php` passes.
2. **Session 2 (Robots directives, task `SEO-03`)**:
   - The agent writes the same inverted robots filter in the child theme and runs the gate on the file it modified.
   - The scanner blocks it:
     ```
     [FAIL] [...] [SEO-NOINDEX] line 7: 'noindex' in wp_robots filter is only guarded by broad or inverted condition(s) `!is_single()`. Risk of de-indexing whole sections: restrict it to is_search(), is_404() or specific IDs/slugs.
     ❌ Safety verification FAILED. Aborting agent session to prevent production damage.
     ```
   - The agent restricts the condition to `is_search() || is_404()`, re-runs the gate, and it passes.
3. **Session 3 (Schema graph, task `SEO-02`)**:
   - The agent builds a connected JSON-LD graph and runs `node tools/schema-graph-validator.js build/schema-home.json`.
   - The validator reports no error: Google's required properties are present and every `@id` reference resolves. The final check is Google's Rich Results Test on the deployed page.
4. **Outcome**:
   - The de-indexing filter never reaches production, and each session leaves verified evidence in `progress.md`.
