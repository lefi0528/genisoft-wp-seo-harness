# Lecture 10: Runtime Guards & Active Rollback

## The Role of the Must-Use Plugin (`wp-seo-guard.php`)

Static code analysis (linting and regex scanning) catches 95% of common mistakes. But what happens if an agent writes syntactically valid code that produces a dynamic runtime fault?

Example: A recursive rewrite rule that causes an infinite 301 redirect loop, or a dynamic query that evaluates to `noindex` only on the live homepage.

---

## The Runtime Shield

By deploying `tools/wp-seo-guard.php` to `wp-content/mu-plugins/`, we establish an active defense layer that runs on every HTTP request:

1. **Homepage NoIndex Neutralizer**:
   Intercepts `wp_robots` at priority `9999`. If `is_front_page()` or `is_home()` contains `noindex`, it strips the directive and logs an emergency alert to `debug.log`.
2. **Canonical URL Normalizer**:
   Forces `https://` and trailing slash consistency on all output canonicals.
3. **Redirect Loop Trap**:
   Monitors `template_redirect` execution. If an agent triggers more than 3 consecutive redirects in a single request, the guard halts with HTTP 508 and a human-readable diagnostic message.

This guarantees that even in worst-case agent failure, the production site remains online and indexable.
