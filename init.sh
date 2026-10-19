#!/usr/bin/env bash
# ==============================================================================
# Genisoft WordPress SEO Harness - Initialization & Health Check
# ==============================================================================

set -e

echo "🚀 Initializing Genisoft WordPress SEO Harness..."

# 1. Check Node.js
if command -v node >/dev/null 2>&1; then
    echo "  ✔ Node.js detected: $(node -v)"
else
    echo "  ⚠ Node.js not found in PATH. Install Node.js to use schema-graph-validator.js."
fi

# 2. Check PHP
if command -v php >/dev/null 2>&1; then
    echo "  ✔ PHP detected: $(php -v | head -n 1)"
else
    echo "  ⚠ PHP CLI not found in PATH."
fi

# 3. Self-test Schema Validator
if [ -f "tools/schema-graph-validator.js" ]; then
    node tools/schema-graph-validator.js --test
fi

# 4. Self-test PHP Safety Verifier
if [ -f "tools/verify-seo-safety.php" ] && command -v php >/dev/null 2>&1; then
    php tools/verify-seo-safety.php --self-test
fi

echo "✔ Harness environment ready. Review templates/AGENTS.md and start your sprint!"
