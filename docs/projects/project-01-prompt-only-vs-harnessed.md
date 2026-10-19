# Project 01: Prompt-Only vs. Minimal Harness

## Objective
Experience firsthand how an unconstrained coding agent introduces fatal SEO regressions, and how the Genisoft verification scanner catches it immediately.

---

## Step 1: Inspect the Starter Fixture
Open `projects/project-01-baseline-vs-harnessed/starter/vulnerable-seo-functions.php`.

Notice the typical agent-generated bug:
```php
add_filter('wp_robots', function($robots) {
    if (!is_single()) {
        $robots['noindex'] = true; // BUG: Sitewide de-indexing
    }
    return $robots;
});
```

---

## Step 2: Run the Verification Gate
Run the Genisoft safety scanner on the starter code:
```bash
php tools/verify-seo-safety.php projects/project-01-baseline-vs-harnessed/starter/vulnerable-seo-functions.php
```

### Scanner Output:
```text
=== Genisoft WordPress SEO Safety Report ===
Files scanned: 1

CRITICAL Violations (3):
  [FAIL] [projects/project-01-baseline-vs-harnessed/starter/vulnerable-seo-functions.php] [SEO-NOINDEX] line 7: 'noindex' in wp_robots filter is only guarded by broad or inverted condition(s) `!is_single()`. Risk of de-indexing whole sections: restrict it to is_search(), is_404() or specific IDs/slugs.
  [FAIL] [projects/project-01-baseline-vs-harnessed/starter/vulnerable-seo-functions.php] [SEO-CANONICAL-HTTP] line 13: Insecure HTTP canonical URL detected. Canonical tags must always use HTTPS.
  [FAIL] [projects/project-01-baseline-vs-harnessed/starter/vulnerable-seo-functions.php] [SEO-META-UNESCAPED] line 16: Unescaped dynamic output `$_GET['search']` in the 'content' attribute of a meta tag. Must wrap with esc_attr() (esc_url() for URLs).

❌ Safety verification FAILED. Aborting agent session to prevent production damage.
```

---

## Step 3: Inspect the Solution
Open `projects/project-01-baseline-vs-harnessed/solution/guarded-seo-functions.php`.

Notice the corrected conditional and escaped outputs:
```bash
php tools/verify-seo-safety.php projects/project-01-baseline-vs-harnessed/solution/guarded-seo-functions.php
```

Output:
```text
✔ All SEO Safety Gates PASSED. Zero critical regressions detected.
```
