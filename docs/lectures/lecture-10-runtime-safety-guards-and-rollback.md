# Lecture 10: Runtime Guards & Active Rollback

## The Role of the Must-Use Plugin (`wp-seo-guard.php`)

Static code analysis (linting and regex scanning) catches 95% of common mistakes. But what happens if an agent writes syntactically valid code that produces a dynamic runtime fault?

Example: A recursive rewrite rule that causes an infinite 301 redirect loop, or a dynamic query that evaluates to `noindex` only on the live homepage.

---

## The Runtime Shield

By deploying `tools/wp-seo-guard.php` to `wp-content/mu-plugins/`, we establish an active defense layer that runs on every HTTP request:

1. **Homepage NoIndex Neutralizer**:
   Intercepts `wp_robots` at priority `9999`. If `is_front_page()` or `is_home()` contains `noindex`, it strips the directive and logs an emergency alert to `debug.log`. A site with "Discourage search engines" enabled (staging) keeps its noindex.
2. **Canonical URL Normalizer**:
   Filters `get_canonical_url` (applied by `wp_get_canonical_url()`): forces `https://` on HTTPS sites and aligns the trailing slash with the permalink structure, so the canonical never points to a redirect.
3. **Self-Redirect Trap**:
   Filters `wp_redirect`. When a redirect targets the URL being requested (including the classic `is_ssl()` loop behind a TLS proxy), the guard halts with HTTP 508 and a human-readable diagnostic message. Multi-hop loops (A → B → A) span several requests: detect them with an external crawl such as `curl -IL --max-redirs 5`.

These guards contain the most common runtime faults. They complement, not replace, the static verification gates and a post-deploy crawl.
