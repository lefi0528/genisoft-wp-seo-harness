#!/usr/bin/env bash
# ==============================================================================
# Regression test: tools/lint-php.sh must fail on a PHP syntax error.
# Usage: bash tests/lint-php.test.sh
# ==============================================================================

set -uo pipefail
cd "$(dirname "$0")/.."

tmp="$(mktemp -d)"
trap 'rm -rf "$tmp"' EXIT

echo "Running lint-php.sh regression tests..."

printf '<?php\necho "ok";\n' > "$tmp/valid.php"
if ! bash tools/lint-php.sh "$tmp" >/dev/null; then
    echo "  ✘ lint-php.sh rejected a valid PHP file"
    exit 1
fi
echo "  ✔ Valid PHP files pass"

printf '<?php\nfunction (\n' > "$tmp/broken.php"
if bash tools/lint-php.sh "$tmp" >/dev/null 2>&1; then
    echo "  ✘ lint-php.sh accepted a PHP syntax error"
    exit 1
fi
echo "  ✔ A PHP syntax error fails the lint"

if bash tools/lint-php.sh >/dev/null 2>&1; then
    echo "  ✘ lint-php.sh without a path passed"
    exit 1
fi
echo "  ✔ Linting without a path is a usage error"
