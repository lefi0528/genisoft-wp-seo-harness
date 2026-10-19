# Lecture 02: The WordPress SEO Threat Model

When an autonomous AI agent operates on a WordPress site, what are the worst things that can happen to organic search traffic?

We categorize these risks into **6 Catastrophic SEO Failure Modes**.

---

## 1. Accidental Sitewide De-Indexing (Threat Severity: CRITICAL)

### How It Happens
An agent attempts to add a `noindex` directive to internal search pages (`is_search()`) or archive pagination. Due to flawed PHP conditional logic:
```php
// THE FATAL FLAW
add_filter('wp_robots', function($robots) {
    if (!is_single()) { // Intended: only index single posts...
        $robots['noindex'] = true; // CATASTROPHE: Homepage, pages, and categories are now noindexed!
    }
    return $robots;
});
```

### Business Impact
Googlebot crawls the homepage, observes `<meta name="robots" content="noindex" />`, and purges the domain from index results within 48 to 72 hours.

---

## 2. Canonical URL Circular Loops & Scheme Inconsistencies (Threat Severity: HIGH)

### How It Happens
The agent attempts to generate canonical tags manually using `$_SERVER['REQUEST_URI']`:
```php
// THE FLAW
echo '<link rel="canonical" href="http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . '" />';
```
- Emits insecure `http://` instead of `https://`.
- Preserves tracking query parameters (`?gclid=...`, `?utm_source=...`), destroying canonical consolidation.
- Creates relative URLs (`href="/blog/"`) which fail strict search engine parsing.

---

## 3. Schema.org Entity Fragmentation & Hallucination (Threat Severity: MEDIUM)

### How It Happens
The agent writes several independent `<script type="application/ld+json">` tags across different templates:
- One tag contains an isolated `Article`.
- Another contains an `Organization` without a logo.
- Another contains a `Rating` without a target product.
- It invents non-standard Schema types or references non-existent `@id` nodes.

Google's Rich Results Testing Tool flags errors, disqualifying the site from rich snippets, review stars, and merchant listings.

---

## 4. XSS & SEO Injection via Unescaped Meta (Threat Severity: HIGH)

```php
// THE FLAW
<meta name="description" content="<?php echo get_post_meta($post->ID, 'custom_desc', true); ?>" />
```
If `custom_desc` contains double quotes (`"`), the HTML tag breaks:
`<meta name="description" content="Best "agency" in town" />`
Search engine crawlers truncate the description, or malicious input breaks into the DOM.

---

## The Harness Defense Matrix

| Threat | Unharnessed Behavior | Genisoft Harness Defense |
|---|---|---|
| Sitewide NoIndex | Silent execution | Intercepted at compile-time by `verify-seo-safety.php` + runtime block in `wp-seo-guard.php` |
| Insecure Canonicals | Allowed | Regex safety gate flags HTTP/relative canonicals |
| Fragmented Schema | Broken JSON-LD | Validated by `schema-graph-validator.js` with `@id` graph consistency |
| Unescaped Meta | Vulnerable | Enforces `esc_attr()` requirement |
