#!/usr/bin/env bash
# ==============================================================================
# Genisoft WordPress SEO Harness - PHP Syntax Linter
# Runs `php -l` on every PHP file under the given paths and exits non-zero when
# any file has a syntax error (`find -exec php -l {} \;` always exits 0).
# ==============================================================================

set -euo pipefail

if [[ "$#" -eq 0 ]]; then
    echo "Usage: bash tools/lint-php.sh <file-or-directory> [more paths...]" >&2
    exit 2
fi

for path in "$@"; do
    if [[ ! -e "$path" ]]; then
        echo "Path not found: $path" >&2
        exit 2
    fi
done

PHP_BIN="${PHP_BIN:-php}"
checked=0
failed=0

while IFS= read -r -d '' file; do
    checked=$((checked + 1))
    if ! output=$("$PHP_BIN" -l "$file" 2>&1); then
        echo "$output"
        failed=$((failed + 1))
    fi
done < <(find "$@" -type f -name '*.php' -print0)

echo "PHP files linted: ${checked}, with syntax errors: ${failed}"
[[ "$failed" -eq 0 ]]
