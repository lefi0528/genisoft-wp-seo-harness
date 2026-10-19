# Lecture 06: Session Init & WP-CLI

## Why Initialization Needs Its Own Phase

Never allow an AI agent to write code in an unverified environment. If the local PHP binary is missing, Node dependencies are uninstalled, or the database connection is down, the agent will hallucinate debugging steps and corrupt existing files.

---

## The `init.sh` Bootstrap Sequence

Every agent session must start by executing `bash init.sh`:

```bash
#!/usr/bin/env bash
set -e

# 1. Verify Node.js runtime for Schema validation
node -v >/dev/null 2>&1 || { echo "Node.js required"; exit 1; }

# 2. Verify PHP CLI runtime for safety scanning
php -v >/dev/null 2>&1 || { echo "PHP CLI required"; exit 1; }

# 3. Run self-tests on verification tools
node tools/schema-graph-validator.js --test
php tools/verify-seo-safety.php --self-test

# 4. Check WordPress health (if WP-CLI available)
if command -v wp >/dev/null 2>&1; then
    wp core is-installed || { echo "WP not installed"; exit 1; }
fi
```

If initialization fails, execution halts immediately. The agent is forced to fix environment prerequisites before touching production code.
