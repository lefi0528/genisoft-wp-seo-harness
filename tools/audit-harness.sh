#!/usr/bin/env bash
# ==============================================================================
# Genisoft WordPress SEO Harness - Repository Audit Script
# Scores any WordPress repo from 0 to 100 based on the 5 Harness Subsystems.
# Points are earned by working artifacts, not by files merely existing: the
# instructions must state the invariants, the backlog must be valid JSON with
# verification gates, and the verification tools must catch a known bug.
# Author: Abdelmonem Lefi (Genisoft)
# ==============================================================================

set -uo pipefail

TARGET_DIR="${1:-.}"
SCORE=0
MAX_SCORE=100
PHP_BIN="${PHP_BIN:-php}"
NODE_BIN="${NODE_BIN:-node}"

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

if [[ ! -d "$TARGET_DIR" ]]; then
    echo "Directory not found: $TARGET_DIR" >&2
    exit 2
fi

WORK_DIR="$(mktemp -d)"
trap 'rm -rf "$WORK_DIR"' EXIT

# Prints the first non-empty file among the candidates (relative to TARGET_DIR)
first_file() {
    local candidate
    for candidate in "$@"; do
        if [[ -s "${TARGET_DIR}/${candidate}" ]]; then
            echo "${TARGET_DIR}/${candidate}"
            return 0
        fi
    done
    return 1
}

# report <earned> <max> <detail>
report() {
    local earned=$1 max=$2 detail=$3
    if [[ $earned -eq $max ]]; then
        echo -e "${GREEN}PASS (+${earned} pts)${NC}"
    elif [[ $earned -gt 0 ]]; then
        echo -e "${YELLOW}PARTIAL (+${earned}/${max} pts) - ${detail}${NC}"
    else
        echo -e "${RED}FAIL (0/${max}) - ${detail}${NC}"
    fi
    SCORE=$((SCORE + earned))
}

# expect_exit <expected-code> <command...>
expect_exit() {
    local expected=$1
    shift
    "$@" >/dev/null 2>&1
    [[ $? -eq $expected ]]
}

echo -e "${CYAN}=== Auditing WordPress SEO Harness in: ${TARGET_DIR} ===${NC}\n"

INSTRUCTIONS="$(first_file AGENTS.md CLAUDE.md templates/AGENTS.md || true)"
TASKS="$(first_file seo_tasks.json templates/seo_tasks.json || true)"

# 1. Instructions Subsystem (20 pts): present and stating the SEO invariants
echo -n "Checking Subsystem 1: Instructions (AGENTS.md / CLAUDE.md)... "
earned=0
detail="Missing AGENTS.md or CLAUDE.md"
if [[ -n "$INSTRUCTIONS" ]]; then
    earned=10
    detail="Instructions do not state the noindex, canonical and escaping invariants"
    if grep -qi "noindex" "$INSTRUCTIONS" && grep -qi "canonical" "$INSTRUCTIONS" && grep -qiE "esc_attr|escap" "$INSTRUCTIONS"; then
        earned=20
    fi
fi
report "$earned" 20 "$detail"

# 2. State Persistence Subsystem (20 pts): valid task backlog with gates + progress log
echo -n "Checking Subsystem 2: State Persistence (seo_tasks.json & progress.md)... "
earned=0
details=()
if [[ -z "$TASKS" ]]; then
    details+=("Missing seo_tasks.json")
elif command -v "$NODE_BIN" >/dev/null 2>&1 && "$NODE_BIN" -e '
    const tasks = JSON.parse(require("fs").readFileSync(process.argv[1], "utf8")).tasks;
    process.exit(Array.isArray(tasks) && tasks.length > 0 && tasks.every((t) => t && t.id && t.verification_gate) ? 0 : 1);
' "$TASKS" >/dev/null 2>&1; then
    earned=$((earned + 10))
else
    details+=("seo_tasks.json is not a valid task list with verification gates (checked with Node.js)")
fi
if first_file progress.md templates/progress.md >/dev/null; then
    earned=$((earned + 10))
else
    details+=("Missing progress.md")
fi
report "$earned" 20 "${details[*]:-}"

# 3. Verification Subsystem (20 pts): the tools must catch a known bug and pass clean code
echo -n "Checking Subsystem 3: Verification (verify-seo-safety.php / schema-validator)... "
earned=0
details=()
cat > "$WORK_DIR/bad.php" <<'PHP'
<?php
add_filter('wp_robots', function ($robots) { $robots['noindex'] = true; return $robots; });
PHP
cat > "$WORK_DIR/good.php" <<'PHP'
<?php
add_filter('wp_robots', function ($robots) { if (is_search()) { $robots['noindex'] = true; } return $robots; });
PHP
echo '{"@context":"https://schema.org","@type":"Product","name":"P","offers":{"@type":"Offer"}}' > "$WORK_DIR/bad.json"
echo '{"@context":"https://schema.org","@type":"Product","name":"P","offers":{"@type":"Offer","price":10,"priceCurrency":"EUR"}}' > "$WORK_DIR/good.json"

SCANNER="${TARGET_DIR}/tools/verify-seo-safety.php"
if [[ ! -s "$SCANNER" ]]; then
    details+=("Missing tools/verify-seo-safety.php")
elif ! command -v "$PHP_BIN" >/dev/null 2>&1; then
    details+=("PHP CLI not found: cannot run verify-seo-safety.php")
elif expect_exit 1 "$PHP_BIN" "$SCANNER" "$WORK_DIR/bad.php" && expect_exit 0 "$PHP_BIN" "$SCANNER" "$WORK_DIR/good.php"; then
    earned=$((earned + 10))
else
    details+=("verify-seo-safety.php does not catch an unconditional noindex")
fi

VALIDATOR="${TARGET_DIR}/tools/schema-graph-validator.js"
if [[ ! -s "$VALIDATOR" ]]; then
    details+=("Missing tools/schema-graph-validator.js")
elif ! command -v "$NODE_BIN" >/dev/null 2>&1; then
    details+=("Node.js not found: cannot run schema-graph-validator.js")
elif expect_exit 1 "$NODE_BIN" "$VALIDATOR" "$WORK_DIR/bad.json" && expect_exit 0 "$NODE_BIN" "$VALIDATOR" "$WORK_DIR/good.json"; then
    earned=$((earned + 10))
else
    details+=("schema-graph-validator.js does not catch a Product offer without price")
fi
report "$earned" 20 "${details[*]:-}"

# 4. Scope Governance (20 pts): Definition of Done + one task per session
echo -n "Checking Subsystem 4: Scope Control (Atomic task backlog & DoD)... "
earned=0
details=()
if [[ -n "$INSTRUCTIONS" ]] && grep -qi "Definition of Done" "$INSTRUCTIONS"; then
    earned=$((earned + 10))
else
    details+=("No Definition of Done in the instructions")
fi
if [[ -n "$INSTRUCTIONS" ]] && grep -qiE "exactly one|one task|1 task" "$INSTRUCTIONS"; then
    earned=$((earned + 10))
else
    details+=("No one-task-per-session rule in the instructions")
fi
report "$earned" 20 "${details[*]:-}"

# 5. Session Lifecycle (20 pts): bootstrap script, handoff template, runtime guard
echo -n "Checking Subsystem 5: Session Lifecycle (init.sh, session-handoff.md, wp-seo-guard.php)... "
earned=0
details=()
if [[ -s "${TARGET_DIR}/init.sh" ]] && bash -n "${TARGET_DIR}/init.sh" 2>/dev/null; then
    earned=$((earned + 10))
else
    details+=("Missing or invalid init.sh")
fi
if first_file session-handoff.md templates/session-handoff.md >/dev/null; then
    earned=$((earned + 5))
else
    details+=("Missing session-handoff.md")
fi
GUARD="${TARGET_DIR}/tools/wp-seo-guard.php"
if [[ -s "$GUARD" ]] && grep -q "get_canonical_url" "$GUARD" && command -v "$PHP_BIN" >/dev/null 2>&1 && "$PHP_BIN" -l "$GUARD" >/dev/null 2>&1; then
    earned=$((earned + 5))
else
    details+=("Missing or invalid tools/wp-seo-guard.php")
fi
report "$earned" 20 "${details[*]:-}"

echo -e "\n--------------------------------------------------"
echo -e "FINAL HARNESS SCORE: ${CYAN}${SCORE} / ${MAX_SCORE}${NC}"
echo -e "--------------------------------------------------"

if [[ "$SCORE" -ge 80 ]]; then
    echo -e "${GREEN}✔ Production-Grade Harness. Certified for Autonomous Agent Operations.${NC}\n"
    exit 0
else
    echo -e "${YELLOW}⚠ Sub-optimal Harness. Review missing artifacts above before launching agents.${NC}\n"
    exit 1
fi
