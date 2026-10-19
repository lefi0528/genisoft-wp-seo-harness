# Project 03: Runtime Safety Guard Deployment

## Objective
Deploy the Must-Use plugin `tools/wp-seo-guard.php` to contain the runtime faults static gates cannot see: an accidental homepage noindex, an insecure canonical, and a redirect back to the requested URL.

---

## Step 1: Run the Simulations
`projects/project-03-runtime-safety-guard/solution/test-runtime-interception.php` loads the real `tools/wp-seo-guard.php` with minimal WordPress stubs and replays each fault:

```bash
php projects/project-03-runtime-safety-guard/solution/test-runtime-interception.php
```

Output:
```text
Running Genisoft Runtime Safety Guard simulations against tools/wp-seo-guard.php...
  ✔ Simulation 1: an agent adds noindex on the live homepage
      → max-image-preview, index
  ✔ Simulation 2: a staging site ("Discourage search engines") keeps its noindex
      → noindex, nofollow
  ✔ Simulation 3: an insecure canonical is normalized with its query string intact
      → https://example.com/services/?paged=2&filter=seo
  ✔ Simulation 4: a redirect back to the requested URL is halted
      → HTTP 503
All runtime simulations passed successfully!
```

---

## Step 2: Deploy to WordPress
Copy the guard to the Must-Use plugins directory, which WordPress loads before regular plugins and which cannot be deactivated from the admin:

```bash
cp tools/wp-seo-guard.php /path/to/wordpress/wp-content/mu-plugins/
```

`tests/e2e/wordpress-e2e.sh` checks the same behaviors on a real WordPress installation (SQLite, PHP built-in server): it is the end-to-end proof that the guard works once deployed.
