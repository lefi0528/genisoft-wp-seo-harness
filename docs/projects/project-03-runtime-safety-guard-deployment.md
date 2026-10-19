# Project 03: Runtime Safety Guard Deployment

## Objective
Deploy the Must-Use plugin `tools/wp-seo-guard.php` to safeguard live WordPress executions against runtime redirect loops and homepage de-indexing.

---

## Hands-On Verification
Inspect `projects/project-03-runtime-safety-guard/solution/test-runtime-interception.php`.

Execute the simulation:
```bash
php projects/project-03-runtime-safety-guard/solution/test-runtime-interception.php
```

Output:
```text
Simulation 1: Attempted homepage noindex...
  ✔ Intercepted: Homepage noindex was neutralized at runtime.
Simulation 2: Insecure canonical URL...
  ✔ Normalized: http:// converted to https:// with trailing slash.
```
