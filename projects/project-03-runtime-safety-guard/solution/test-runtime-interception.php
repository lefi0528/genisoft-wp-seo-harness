<?php
declare(strict_types=1);

/**
 * Project 03: runs the real tools/wp-seo-guard.php against minimal WordPress stubs
 * and simulates the runtime faults it must contain.
 * Usage: php projects/project-03-runtime-safety-guard/solution/test-runtime-interception.php
 */

define('ABSPATH', __DIR__ . '/');

// Minimal WordPress environment: one live HTTPS site whose permalinks end with a slash
$site = ['blog_public' => '1', 'front_page' => true];
$hooks = [];

function add_filter(string $hook, $callback, int $priority = 10, int $accepted_args = 1): bool {
    $GLOBALS['hooks'][$hook][] = $callback;
    return true;
}
function add_action(string $hook, $callback, int $priority = 10, int $accepted_args = 1): bool {
    return add_filter($hook, $callback, $priority, $accepted_args);
}
function get_option(string $option) {
    return $option === 'blog_public' ? $GLOBALS['site']['blog_public'] : false;
}
function is_front_page(): bool {
    return $GLOBALS['site']['front_page'];
}
function is_home(): bool {
    return false;
}
function is_ssl(): bool {
    return true;
}
function home_url(string $path = ''): string {
    return 'https://example.com' . $path;
}
function wp_parse_url(string $url, int $component = -1) {
    return parse_url($url, $component);
}
function user_trailingslashit(string $path): string {
    return rtrim($path, '/') . '/';
}
function wp_die($message = '', $title = '', $args = []): void {
    throw new RuntimeException('HTTP ' . ($args['response'] ?? 500));
}

require __DIR__ . '/../../../tools/wp-seo-guard.php';

use Genisoft\WpSeoHarness\WpSeoGuard;

foreach ($hooks['plugins_loaded'] as $callback) {
    $callback();
}

$failures = 0;
$simulate = function (string $title, callable $run, string $expected) use (&$failures): void {
    try {
        $actual = $run();
    } catch (RuntimeException $e) {
        $actual = $e->getMessage();
    }
    $ok = $actual === $expected;
    $failures += $ok ? 0 : 1;
    echo $ok ? "  ✔ $title\n      → $actual\n" : "  ❌ $title\n      expected: $expected\n      got:      $actual\n";
};
$robots = fn(array $directives): string => implode(', ', array_keys(array_filter($directives)));

echo "Running Genisoft Runtime Safety Guard simulations against tools/wp-seo-guard.php...\n";

$simulate('Simulation 1: an agent adds noindex on the live homepage', function () use ($robots): string {
    return $robots(WpSeoGuard::guardRobotsMeta(['noindex' => true, 'max-image-preview' => 'large']));
}, 'max-image-preview, index');

$simulate('Simulation 2: a staging site ("Discourage search engines") keeps its noindex', function () use ($robots): string {
    $GLOBALS['site']['blog_public'] = '0';
    $result = $robots(WpSeoGuard::guardRobotsMeta(['noindex' => true, 'nofollow' => true]));
    $GLOBALS['site']['blog_public'] = '1';
    return $result;
}, 'noindex, nofollow');

$simulate('Simulation 3: an insecure canonical is normalized with its query string intact', function (): string {
    return WpSeoGuard::guardCanonicalUrl('http://example.com/services?paged=2&filter=seo');
}, 'https://example.com/services/?paged=2&filter=seo');

$simulate('Simulation 4: a redirect back to the requested URL is halted', function (): string {
    $_SERVER['HTTP_HOST'] = 'example.com';
    $_SERVER['REQUEST_URI'] = '/services/';
    return (string) WpSeoGuard::guardSelfRedirect('https://example.com/services/', 301);
}, 'HTTP 503');

if ($failures > 0) {
    echo "$failures runtime simulation(s) failed.\n";
    exit(1);
}
echo "All runtime simulations passed successfully!\n";
