#!/usr/bin/env bash
# ==============================================================================
# End-to-end test on a real WordPress: installs the latest WordPress with the
# SQLite Database Integration plugin in a temporary directory, serves it with
# PHP's built-in server, and checks tools/wp-seo-guard.php and
# examples/genisoft-live-seo-enforcer.php on real HTTP responses.
# Requires: PHP 8.1+ with pdo_sqlite, Node.js 18+, curl, unzip, network access.
# Usage: bash tests/e2e/wordpress-e2e.sh
# ==============================================================================

set -euo pipefail
cd "$(dirname "$0")/../.."
ROOT="$(pwd)"

PHP_BIN="${PHP_BIN:-php}"
NODE_BIN="${NODE_BIN:-node}"
PORT="${E2E_PORT:-8899}"
BASE="http://127.0.0.1:${PORT}"
SITE="https://127.0.0.1:${PORT}"
WORK="$(mktemp -d)"
WP="$WORK/wordpress"
SERVER_PID=""
failures=0

cleanup() {
    if [[ -n "$SERVER_PID" ]]; then
        kill "$SERVER_PID" 2>/dev/null || true
    fi
    rm -rf "$WORK"
}
trap cleanup EXIT

echo "Downloading WordPress and the SQLite Database Integration plugin..."
curl -fsSL https://wordpress.org/latest.zip -o "$WORK/wordpress.zip"
curl -fsSL https://downloads.wordpress.org/plugin/sqlite-database-integration.latest-stable.zip -o "$WORK/sqlite.zip"
unzip -q "$WORK/wordpress.zip" -d "$WORK"
unzip -q "$WORK/sqlite.zip" -d "$WP/wp-content/plugins"

SQLITE_PLUGIN_DIR="$WP/wp-content/plugins/sqlite-database-integration"
sed -e "s#{SQLITE_IMPLEMENTATION_FOLDER_PATH}#${SQLITE_PLUGIN_DIR}#" \
    -e "s#{SQLITE_PLUGIN}#sqlite-database-integration/load.php#" \
    "$SQLITE_PLUGIN_DIR/db.copy" > "$WP/wp-content/db.php"

cat > "$WP/wp-config.php" <<'PHP'
<?php
define('DB_NAME', 'wordpress');
define('DB_USER', '');
define('DB_PASSWORD', '');
define('DB_HOST', '');
define('DB_CHARSET', 'utf8');
define('DB_COLLATE', '');
define('DB_DIR', __DIR__ . '/wp-content/database/');
define('DB_FILE', 'e2e.sqlite');
$table_prefix = 'wp_';
define('WP_DEBUG', true);
define('WP_DEBUG_DISPLAY', false);
define('WP_DEBUG_LOG', __DIR__ . '/debug.log');
define('DISABLE_WP_CRON', true);
define('AUTOMATIC_UPDATER_DISABLED', true);
define('WP_HTTP_BLOCK_EXTERNAL', true);
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}
require_once ABSPATH . 'wp-settings.php';
PHP

# PHP built-in server router: static files as-is, everything else to WordPress, seen as HTTPS
cat > "$WORK/router.php" <<'PHP'
<?php
$_SERVER['HTTPS'] = 'on';
$path = (string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path !== '/' && is_file($_SERVER['DOCUMENT_ROOT'] . $path)) {
    return false;
}
chdir($_SERVER['DOCUMENT_ROOT']);
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = $_SERVER['DOCUMENT_ROOT'] . '/index.php';
require $_SERVER['DOCUMENT_ROOT'] . '/index.php';
PHP

cat > "$WORK/install.php" <<'PHP'
<?php
[, $wordpress, $site] = $argv;
$_SERVER['HTTP_HOST'] = (string) parse_url($site, PHP_URL_HOST) . ':' . parse_url($site, PHP_URL_PORT);
$_SERVER['HTTPS'] = 'on';
define('WP_INSTALLING', true);
require $wordpress . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/upgrade.php';

wp_install('Harness E2E', 'admin', 'admin@example.com', true, '', wp_generate_password(32));
update_option('siteurl', $site);
update_option('home', $site);
global $wp_rewrite;
$wp_rewrite->set_permalink_structure('/%postname%/');
$wp_rewrite->flush_rules(false);
wp_insert_post([
    'post_type' => 'page',
    'post_status' => 'publish',
    'post_name' => 'agence-web',
    'post_title' => 'Agence Web',
    'post_content' => 'E2E target page of examples/genisoft-live-seo-enforcer.php',
]);
echo "WordPress " . get_bloginfo('version') . " installed\n";
PHP

"$PHP_BIN" "$WORK/install.php" "$WP" "$SITE"

mkdir -p "$WP/wp-content/mu-plugins"
cp tools/wp-seo-guard.php examples/genisoft-live-seo-enforcer.php tests/e2e/e2e-agent-mistakes.php "$WP/wp-content/mu-plugins/"

"$PHP_BIN" -S "127.0.0.1:${PORT}" -t "$WP" "$WORK/router.php" > "$WORK/server.log" 2>&1 &
SERVER_PID=$!
curl -s -o /dev/null --retry 30 --retry-connrefused --retry-delay 1 "$BASE/"

# fetch <path>: sets STATUS, saves the body to $WORK/body.html
fetch() {
    STATUS="$(curl -s -o "$WORK/body.html" -w '%{http_code}' "$BASE$1")"
}
robots() {
    grep -oE "<meta name=['\"]robots['\"][^>]*>" "$WORK/body.html" || true
}
canonical() {
    grep -oE "<link rel=['\"]canonical['\"] href=['\"][^'\"]*" "$WORK/body.html" | sed -E "s/.*href=['\"]//" || true
}
check() {
    if [[ "$2" == "true" ]]; then
        echo "  ✔ $1"
    else
        echo "  ✘ $1 (${3:-})"
        failures=$((failures + 1))
    fi
}
is() { [[ "$1" == "$2" ]] && echo true || echo false; }
has() { grep -qF -- "$1" "$WORK/body.html" && echo true || echo false; }
lacks() { grep -qF -- "$1" "$WORK/body.html" && echo false || echo true; }

echo "Running end-to-end checks on $SITE..."

# Runtime guard: robots
fetch "/?e2e=noindex"
check "Guard: an agent's sitewide noindex is neutralized on the homepage" "$(robots | grep -q noindex && echo false || echo true)" "$(robots)"
fetch "/hello-world/?e2e=noindex"
check "Guard: noindex on other pages is left to the site" "$(robots | grep -q noindex && echo true || echo false)" "$(robots)"
fetch "/?e2e=staging"
check "Guard: a staging site keeps its noindex on the homepage" "$(robots | grep -q noindex && echo true || echo false)" "$(robots)"

# Runtime guard: canonical
fetch "/hello-world/?e2e=canonical"
check "Guard: canonical upgraded to HTTPS with the permalink trailing slash" "$(is "$(canonical)" "$SITE/hello-world/")" "$(canonical)"
fetch "/hello-world?e2e=canonical-noslash"
check "Guard: canonical follows a permalink structure without trailing slash" "$(is "$(canonical)" "$SITE/hello-world")" "$(canonical)"

# Runtime guard: self-redirect
fetch "/?e2e=loop"
check "Guard: a redirect to the requested URL is halted with HTTP 503" "$(is "$STATUS" 503)" "HTTP $STATUS"

# Example plugin
fetch "/agence-web/"
check "Example: target page answers 200" "$(is "$STATUS" 200)" "HTTP $STATUS"
check "Example: title and meta description are printed" "$(has 'Agence web Grenoble spécialisée PrestaShop et SEO | Genisoft</title>')"
check "Example: no og:image pointing to a missing file" "$(lacks 'og:image')"
cp "$WORK/body.html" "$WORK/agence-web.html"
check "Example: its JSON-LD graph passes schema-graph-validator.js" "$("$NODE_BIN" tools/schema-graph-validator.js "$WORK/agence-web.html" >/dev/null 2>&1 && echo true || echo false)" "$("$NODE_BIN" tools/schema-graph-validator.js "$WORK/agence-web.html" 2>&1 | tail -3 | tr '\n' ' ')"
fetch "/sample-page/"
check "Example: other pages are untouched" "$(lacks 'Genisoft SEO Harness')"
fetch "/agence-web/?e2e=yoast"
check "Example: silent when an SEO plugin is active (no duplicate tags)" "$(lacks 'Genisoft SEO Harness')"

# PHP notices, warnings or errors raised by the harness plugins
if [[ -f "$WP/debug.log" ]] && grep -E "PHP (Fatal|Warning|Notice|Deprecated).*(wp-seo-guard|genisoft-live-seo-enforcer)" "$WP/debug.log" >/dev/null; then
    check "No PHP notice, warning or error from the harness plugins" false "$(grep -E 'wp-seo-guard|genisoft-live-seo-enforcer' "$WP/debug.log" | head -3)"
else
    check "No PHP notice, warning or error from the harness plugins" true
fi
check "Guard: the neutralized homepage noindex is logged" "$(grep -q 'GENISOFT HARNESS ALERT' "$WP/debug.log" 2>/dev/null && echo true || echo false)"

if [[ $failures -gt 0 ]]; then
    echo "$failures end-to-end check(s) FAILED."
    exit 1
fi
echo "All end-to-end checks passed on a real WordPress!"
