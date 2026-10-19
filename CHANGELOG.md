# Changelog

## 1.1.0 (2026-10-19)

### Runtime guard (`tools/wp-seo-guard.php` 1.2.0)
- Canonical normalization now runs: it filters `get_canonical_url`, the filter WordPress core applies (`wp_get_canonical_url` does not exist). HTTPS is forced only on HTTPS sites and the trailing slash follows the permalink structure.
- Staging sites ("Discourage search engines") keep their homepage noindex.
- Redirects back to the requested URL (including protocol-relative targets and the `is_ssl()` loop behind a TLS proxy) are halted with HTTP 503. The previous trap could never trigger, and WordPress cannot send 508.

### Verification gates
- `verify-seo-safety.php` rewritten on the PHP tokenizer: no false positive on the harness itself, any noindex (wp_robots, X-Robots-Tag, robots meta) must be guarded by a narrow condition, unescaped meta caught in concatenation, `<?= ?>`, interpolation and `printf`. Self-tests live in `tests/fixtures/safety/`.
- `schema-graph-validator.js` checks Google's documented required and recommended properties, resolves every `@id` (absolute ones included), handles `@type` arrays, nested entities and every JSON-LD block of an HTML page.
- Gates without a target exit 2 instead of passing; every gate in `seo_tasks.json` names its target.
- `audit-harness.sh` scores working artifacts (the tools must catch a known bug), not file presence.
- `init.sh` stops when Node.js 18+ or PHP 8.1+ is missing.

### Tests and CI
- Regression tests for the guard, the scanner, the validator CLI, the lint script and the audit; `tools/lint-php.sh` makes CI fail on PHP syntax errors.
- `tests/e2e/wordpress-e2e.sh` installs the latest WordPress (SQLite) and checks the guard and the example plugin over HTTP; it runs in CI.

### Examples and documentation
- Project 03 runs the real guard. The example plugin no longer references missing images, stays silent when an SEO plugin is active, and only targets its page.
- Documentation claims match what the harness does; the case study is labeled as an illustrative scenario.

## 1.0.0

- Initial release.
