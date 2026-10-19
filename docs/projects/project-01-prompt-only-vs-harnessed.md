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

CRITICAL Violations (1):
  [FAIL] Unconditional 'noindex' detected in wp_robots filter. Risk of de-indexing entire site.

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
