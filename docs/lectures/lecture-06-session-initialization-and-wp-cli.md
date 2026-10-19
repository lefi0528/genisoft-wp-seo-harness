# Lecture 06: Session Init & WP-CLI

## Why Initialization Needs Its Own Phase

Never allow an AI agent to write code in an unverified environment. If the local PHP binary is missing, Node dependencies are uninstalled, or the database connection is down, the agent will hallucinate debugging steps and corrupt existing files.

---

## The `init.sh` Bootstrap Sequence

Every agent session must start by executing `bash init.sh`. The repository's script does the following:

```bash
set -euo pipefail
cd "$(dirname "$0")"

# 1. Node.js 18+ for Schema validation
if ! command -v node >/dev/null 2>&1 || ! node -e 'process.exit(parseInt(process.versions.node, 10) >= 18 ? 0 : 1)'; then
    echo "  ✘ Node.js 18+ is required to run schema-graph-validator.js." >&2
    exit 1
fi

# 2. PHP 8.1+ CLI for safety scanning
if ! command -v php >/dev/null 2>&1 || ! php -r 'exit(PHP_VERSION_ID >= 80100 ? 0 : 1);'; then
    echo "  ✘ PHP 8.1+ CLI is required to run verify-seo-safety.php." >&2
    exit 1
fi

# 3. Self-tests of the verification tools
node tools/schema-graph-validator.js --test
php tools/verify-seo-safety.php --self-test

# 4. WordPress health, when run from a WordPress root with WP-CLI available
if [[ -f wp-config.php ]] && command -v wp >/dev/null 2>&1; then
    if ! wp core is-installed >/dev/null 2>&1; then
        echo "  ✘ wp-config.php found but WordPress is not installed (wp core is-installed failed)." >&2
        exit 1
    fi
fi
```

If initialization fails, execution halts immediately. The agent is forced to fix environment prerequisites before touching production code.
