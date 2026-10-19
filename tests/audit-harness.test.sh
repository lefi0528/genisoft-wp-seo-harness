#!/usr/bin/env bash
# ==============================================================================
# Regression test: tools/audit-harness.sh must score working artifacts, so empty
# or placeholder files can never be certified.
# Usage: bash tests/audit-harness.test.sh
# ==============================================================================

set -uo pipefail
cd "$(dirname "$0")/.."

tmp="$(mktemp -d)"
trap 'rm -rf "$tmp"' EXIT
failures=0

score_of() {
    bash tools/audit-harness.sh "$1" 2>/dev/null | sed 's/\x1b\[[0-9;]*m//g' | sed -n 's/^FINAL HARNESS SCORE: \([0-9]*\) .*/\1/p'
}

check() {
    if eval "$2"; then
        echo "  ✔ $1"
    else
        echo "  ✘ $1 (score: $score)"
        failures=$((failures + 1))
    fi
}

echo "Running audit-harness.sh regression tests..."

score="$(score_of .)"
check "This repository scores 100/100" '[[ "$score" == "100" ]]'

mkdir -p "$tmp/empty"
score="$(score_of "$tmp/empty")"
check "An empty directory scores 0" '[[ "$score" == "0" ]]'

# The four placeholder files that used to be certified 80/100
mkdir -p "$tmp/fake/tools" "$tmp/fake/templates"
echo "Definition of Done" > "$tmp/fake/AGENTS.md"
touch "$tmp/fake/tools/schema-graph-validator.js" "$tmp/fake/init.sh" "$tmp/fake/templates/session-handoff.md"
score="$(score_of "$tmp/fake")"
check "Placeholder files are not certified" '[[ -n "$score" && "$score" -lt 80 ]]'

if bash tools/audit-harness.sh "$tmp/fake" >/dev/null 2>&1; then
    echo "  ✘ Audit exits 0 on placeholder files"
    failures=$((failures + 1))
else
    echo "  ✔ Audit exits non-zero below 80"
fi

[[ $failures -eq 0 ]]
