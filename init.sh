#!/usr/bin/env bash
# ==============================================================================
# Genisoft WordPress SEO Harness - Initialization & Health Check
# Halts with a non-zero exit code when a prerequisite is missing, so an agent
# never starts working in an unverified environment.
# ==============================================================================

set -euo pipefail
cd "$(dirname "$0")"

echo "🚀 Initializing Genisoft WordPress SEO Harness..."

# 1. Node.js 18+ for Schema validation
if ! command -v node >/dev/null 2>&1 || ! node -e 'process.exit(parseInt(process.versions.node, 10) >= 18 ? 0 : 1)'; then
    echo "  ✘ Node.js 18+ is required to run schema-graph-validator.js." >&2
    exit 1
fi
echo "  ✔ Node.js detected: $(node -v)"

# 2. PHP 8.1+ CLI for safety scanning
if ! command -v php >/dev/null 2>&1 || ! php -r 'exit(PHP_VERSION_ID >= 80100 ? 0 : 1);'; then
    echo "  ✘ PHP 8.1+ CLI is required to run verify-seo-safety.php." >&2
    exit 1
fi
echo "  ✔ PHP detected: $(php -r 'echo PHP_VERSION;')"

# 3. Self-tests of the verification tools
node tools/schema-graph-validator.js --test
php tools/verify-seo-safety.php --self-test

# 4. WordPress health, when run from a WordPress root with WP-CLI available
if [[ -f wp-config.php ]] && command -v wp >/dev/null 2>&1; then
    if ! wp core is-installed >/dev/null 2>&1; then
        echo "  ✘ wp-config.php found but WordPress is not installed (wp core is-installed failed)." >&2
        exit 1
    fi
    echo "  ✔ WordPress $(wp core version) installed"
fi

echo "✔ Harness environment ready. Review templates/AGENTS.md and start your sprint!"
