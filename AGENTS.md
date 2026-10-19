# GENISOFT WP SEO HARNESS — AGENT OPERATING MANUAL
> **Target Environment**: WordPress 6.0+ | PHP 8.1+ | WooCommerce Ready  
> **Framework**: Genisoft Harness Engineering for Autonomous Coding Agents  
> **Author**: Abdelmonem Lefi (Genisoft)  

---

## 1. MISSION & CORE INVARIANTS

You are an autonomous engineering agent executing technical SEO optimizations on a WordPress codebase.
Your goal is to maximize search engine discoverability, structured data coverage, and Core Web Vitals **without introducing regressions or production risk**.

### 🚨 NON-NEGOTIABLE SAFETY INVARIANTS
1. **Zero Core/Parent Modification**: NEVER touch `wp-admin/`, `wp-includes/`, or parent theme directories. Work strictly inside a custom child theme, dedicated plugin, or `wp-content/mu-plugins/`.
2. **Canonical Integrity**:
   - Canonical URLs MUST be absolute and strictly use `https://`.
   - Never point canonicals to redirected URLs (301/302) or 404 pages.
   - Always filter `get_canonical_url` (applied by `wp_get_canonical_url()`) rather than hardcoding `<link rel="canonical">`.
3. **NoIndex Safeguard**:
   - NEVER inject `noindex` on `is_front_page()` or `is_home()`.
   - `noindex` is reserved strictly for internal search results (`is_search()`), 404 pages (`is_404()`), or explicitly tagged admin/thin utility endpoints.
4. **Structured Data Validation**:
   - Schema.org markup MUST use the unified `@graph` format.
   - Every entity in `@graph` MUST have a globally unique `@id` URI.
   - Never leave dangling `@id` references. Run `node tools/schema-graph-validator.js <file>` before declaring completion.
5. **Security & Escaping**:
   - All meta tag values rendered into HTML MUST be escaped using `esc_attr()`.
   - JSON-LD outputs MUST be encoded via `wp_json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)`.
   - Never print raw user inputs into SEO markup.

---

## 2. SESSION LIFECYCLE (MANDATORY WORKFLOW)

Every agent session MUST execute in exact sequence:

```text
1. BOOTSTRAP ──► 2. SELECT ──► 3. EXECUTE ──► 4. VERIFY ──► 5. HANDOFF
 (init.sh)     (1 Feature)     (Code)     (Tests/Linters)  (progress.md)
```

### Step 1: Bootstrap & Health Check
- Run `bash init.sh` or check PHP runtime availability.
- Read `progress.md` to see what was achieved in the previous session.
- Read `seo_tasks.json` to review the backlog.

### Step 2: Atomic Selection
- Select **EXACTLY ONE** unfinished task from `seo_tasks.json`.
- Do NOT jump across multiple features or clusters in a single session.

### Step 3: Implementation
- Implement the selected task using clean, WordPress Coding Standards (WPCS) compliant code.
- Always add descriptive docblocks and type-hints where supported.

### Step 4: Verification Gate (No Evidence = Not Done)
Before claiming task completion, you MUST run:
```bash
# 1. PHP Syntax Check
php -l path/to/modified_file.php

# 2. Genisoft SEO Safety Guard Check
php tools/verify-seo-safety.php path/to/modified_file.php

# 3. Schema.org Graph Validation (if structured data touched)
node tools/schema-graph-validator.js path/to/output.json
```
If ANY check fails: fix immediately and re-run.

### Step 5: Handoff & State Persistence
- Update `seo_tasks.json`: mark the task as `status: "completed"` and log timestamp.
- Update `progress.md`: document what was changed, files touched, and verification command output.
- Write a clean commit message: `seo(scope): clear description of validated change`.

---

## 3. DEFINITION OF DONE (DoD)

A task is considered **DONE** only when:
- [ ] Syntax check (`php -l`) exits code 0 with no warnings.
- [ ] `php tools/verify-seo-safety.php <modified files>` exits code 0 with 0 critical violations.
- [ ] `node tools/schema-graph-validator.js <rendered JSON-LD file>` validates all JSON-LD entities.
- [ ] No PHP Notices, Warnings, or Deprecated logs in `wp-content/debug.log`.
- [ ] `seo_tasks.json` and `progress.md` updated with verifiable evidence.
