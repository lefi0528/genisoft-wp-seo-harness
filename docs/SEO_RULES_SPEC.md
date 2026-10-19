# Genisoft WordPress Technical SEO Specification

Enforced rules follow [RFC 2119](https://datatracker.ietf.org/doc/html/rfc2119) standards (*MUST, MUST NOT, SHOULD*).

---

## Rule 1: Canonical URLs (RFC-SEO-01)
- **MUST**: Every indexable page MUST have exactly one `<link rel="canonical">` tag.
- **MUST**: The canonical URL MUST be absolute and use the `https://` protocol scheme.
- **MUST**: The canonical URL MUST match the primary domain host exactly (no www/non-www mismatch).
- **MUST NOT**: Canonical URLs MUST NOT point to URLs returning HTTP 3xx, 4xx, or 5xx status codes.
- **SHOULD**: Filter via `get_canonical_url` (applied by `wp_get_canonical_url()`) rather than echoing hardcoded HTML strings into `wp_head`.

---

## Rule 2: Robots Directives & Indexing (RFC-SEO-02)
- **MUST NOT**: `noindex` MUST NOT be emitted on `is_front_page()` or `is_home()`.
- **MUST**: Emitted robots directives MUST include:
  ```html
  <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1" />
  ```
- **MUST**: Search results pages (`is_search()`) and 404 pages (`is_404()`) MUST be set to `noindex, follow`.

---

## Rule 3: Structured Data Knowledge Graph (RFC-SEO-03)
- **MUST**: All structured data MUST be output as JSON-LD using `<script type="application/ld+json">`.
- **MUST**: Entities MUST be connected within an `@graph` container.
- **MUST**: Entities MUST use stable, canonical `@id` URIs (e.g. `https://example.com/#website`, `https://example.com/#organization`).
- **MUST NOT**: Dangling references (an entity referencing an `@id` not defined in the graph) are strictly forbidden.

---

## Rule 4: Output Security & Escaping (RFC-SEO-04)
- **MUST**: Dynamic attributes inside `<meta>` tags MUST be sanitized and escaped:
  ```php
  <meta name="description" content="<?php echo esc_attr($meta_description); ?>" />
  ```
- **MUST NOT**: Raw user input from `$_GET` or `$_POST` MUST NEVER be rendered into meta tags or Open Graph properties.
