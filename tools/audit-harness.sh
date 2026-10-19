#!/usr/bin/env bash
# ==============================================================================
# Genisoft WordPress SEO Harness - Repository Audit Script
# Scores any WordPress repo from 0 to 100 based on the 5 Harness Subsystems.
# Author: Abdelmonem Lefi (Genisoft)
# ==============================================================================

set -e

TARGET_DIR="${1:-.}"
SCORE=0
MAX_SCORE=100

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

echo -e "${CYAN}=== Auditing WordPress SEO Harness in: ${TARGET_DIR} ===${NC}\n"

# 1. Instructions Subsystem (20 pts)
echo -n "Checking Subsystem 1: Instructions (AGENTS.md / CLAUDE.md)... "
if [ -f "${TARGET_DIR}/AGENTS.md" ] || [ -f "${TARGET_DIR}/CLAUDE.md" ]; then
    SCORE=$((SCORE + 20))
    echo -e "${GREEN}PASS (+20 pts)${NC}"
else
    echo -e "${RED}FAIL (0/20) - Missing AGENTS.md or CLAUDE.md${NC}"
fi

# 2. State Persistence Subsystem (20 pts)
echo -n "Checking Subsystem 2: State Persistence (seo_tasks.json & progress.md)... "
if [ -f "${TARGET_DIR}/templates/seo_tasks.json" ] || [ -f "${TARGET_DIR}/seo_tasks.json" ]; then
    SCORE=$((SCORE + 10))
fi
if [ -f "${TARGET_DIR}/templates/progress.md" ] || [ -f "${TARGET_DIR}/progress.md" ]; then
    SCORE=$((SCORE + 10))
fi
echo -e "${GREEN}PASS (+20 pts)${NC}"

# 3. Verification Subsystem (20 pts)
echo -n "Checking Subsystem 3: Verification (verify-seo-safety.php / schema-validator)... "
if [ -f "${TARGET_DIR}/tools/verify-seo-safety.php" ] || [ -f "${TARGET_DIR}/tools/schema-graph-validator.js" ]; then
    SCORE=$((SCORE + 20))
    echo -e "${GREEN}PASS (+20 pts)${NC}"
else
    echo -e "${RED}FAIL (0/20) - No verification tools found${NC}"
fi

# 4. Scope Governance (20 pts)
echo -n "Checking Subsystem 4: Scope Control (Atomic task backlog & DoD)... "
if grep -qi "Definition of Done" "${TARGET_DIR}/templates/AGENTS.md" 2>/dev/null || grep -qi "Definition of Done" "${TARGET_DIR}/AGENTS.md" 2>/dev/null; then
    SCORE=$((SCORE + 20))
    echo -e "${GREEN}PASS (+20 pts)${NC}"
else
    echo -e "${YELLOW}PARTIAL (+10 pts) - Definition of Done recommended${NC}"
    SCORE=$((SCORE + 10))
fi

# 5. Session Lifecycle (20 pts)
echo -n "Checking Subsystem 5: Session Lifecycle (init.sh & session-handoff.md)... "
if [ -f "${TARGET_DIR}/init.sh" ] && ([ -f "${TARGET_DIR}/templates/session-handoff.md" ] || [ -f "${TARGET_DIR}/session-handoff.md" ]); then
    SCORE=$((SCORE + 20))
    echo -e "${GREEN}PASS (+20 pts)${NC}"
else
    SCORE=$((SCORE + 10))
    echo -e "${YELLOW}PARTIAL (+10 pts)${NC}"
fi

echo -e "\n--------------------------------------------------"
echo -e "FINAL HARNESS SCORE: ${CYAN}${SCORE} / ${MAX_SCORE}${NC}"
echo -e "--------------------------------------------------"

if [ "$SCORE" -ge 80 ]; then
    echo -e "${GREEN}✔ Production-Grade Harness. Certified for Autonomous Agent Operations.${NC}\n"
    exit 0
else
    echo -e "${YELLOW}⚠ Sub-optimal Harness. Review missing artifacts above before launching agents.${NC}\n"
    exit 1
fi
