<?php
/**
 * Plugin Name: E2E Agent Mistakes
 * Description: Test-only Must-Use plugin for tests/e2e/wordpress-e2e.sh. Reproduces, on demand (?e2e=...), the faults an AI agent can introduce, so the end-to-end test can check how the harness contains them. Never deploy it.
 */

$genisoft_e2e_scenario = isset($_GET['e2e']) ? (string) $_GET['e2e'] : '';

switch ($genisoft_e2e_scenario) {
    // Sitewide noindex, e.g. an inverted condition in a wp_robots filter
    case 'noindex':
        add_filter('wp_robots', static fn(array $robots): array => ['noindex' => true] + $robots);
        break;

    // Staging site: "Discourage search engines" is on
    case 'staging':
        add_filter('pre_option_blog_public', static fn(): string => '0');
        break;

    // Canonical built with http:// and without the permalink trailing slash
    case 'canonical':
        add_filter('get_canonical_url', static fn(): string => home_url('/hello-world', 'http'));
        break;

    // Permalinks without trailing slash, canonical built with one
    case 'canonical-noslash':
        add_filter('pre_option_permalink_structure', static fn(): string => '/%postname%');
        add_filter('get_canonical_url', static fn(): string => home_url('/hello-world/', 'http'));
        break;

    // Redirect to the URL being requested
    case 'loop':
        add_action('template_redirect', static function (): void {
            wp_safe_redirect(home_url(add_query_arg([])));
            exit;
        });
        break;

    // An SEO plugin (Yoast) is active
    case 'yoast':
        define('WPSEO_VERSION', 'e2e');
        break;
}
