<?php
declare(strict_types=1);

/**
 * Regression tests for tools/wp-seo-guard.php, run against minimal WordPress stubs.
 * Usage: php tests/guard.test.php
 */

namespace Genisoft\WpSeoHarness {
    // Captures the guard's alerts instead of printing them (unqualified calls resolve here first)
    function error_log(string $message): bool {
        $GLOBALS['wp_logs'][] = $message;
        return true;
    }
}

namespace {
    define('ABSPATH', __DIR__ . '/');

    $GLOBALS['wp_hooks'] = [];
    $GLOBALS['wp_logs'] = [];
    $GLOBALS['wp'] = [];

    function wp_reset(array $state = []): void {
        $GLOBALS['wp'] = $state + [
            'blog_public' => '1', 'front_page' => false, 'home' => false, 'ssl' => true,
            'home_url' => 'https://example.com', 'trailing_slashes' => true,
        ];
        $GLOBALS['wp_logs'] = [];
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['REQUEST_URI'] = '/page/';
        unset($_SERVER['HTTP_X_FORWARDED_PROTO']);
    }

    function add_filter(string $hook, $callback, int $priority = 10, int $accepted_args = 1): bool {
        $GLOBALS['wp_hooks'][$hook][] = $callback;
        return true;
    }

    function add_action(string $hook, $callback, int $priority = 10, int $accepted_args = 1): bool {
        return add_filter($hook, $callback, $priority, $accepted_args);
    }

    function get_option(string $option) {
        return $option === 'blog_public' ? $GLOBALS['wp']['blog_public'] : false;
    }

    function is_front_page(): bool {
        return $GLOBALS['wp']['front_page'];
    }

    function is_home(): bool {
        return $GLOBALS['wp']['home'];
    }

    function is_ssl(): bool {
        return $GLOBALS['wp']['ssl'];
    }

    function home_url(string $path = ''): string {
        return $GLOBALS['wp']['home_url'] . $path;
    }

    function wp_parse_url(string $url, int $component = -1) {
        return parse_url($url, $component);
    }

    function user_trailingslashit(string $path): string {
        return $GLOBALS['wp']['trailing_slashes'] ? rtrim($path, '/') . '/' : rtrim($path, '/');
    }

    function wp_die($message = '', $title = '', $args = []): void {
        throw new RuntimeException('wp_die ' . ($args['response'] ?? 500));
    }

    require __DIR__ . '/../tools/wp-seo-guard.php';

    use Genisoft\WpSeoHarness\WpSeoGuard;

    $failures = 0;
    $check = function (bool $ok, string $label, string $details = '') use (&$failures): void {
        echo $ok ? "  \033[32m✔\033[0m $label\n" : "  \033[31m✘\033[0m $label" . ($details !== '' ? " ($details)" : '') . "\n";
        $failures += $ok ? 0 : 1;
    };
    $redirect = function (string $location): string {
        try {
            $result = WpSeoGuard::guardSelfRedirect($location, 301);
            return $result === $location ? 'kept' : 'changed';
        } catch (RuntimeException $e) {
            return $e->getMessage();
        }
    };

    echo "\033[36mRunning WP SEO Guard regression tests...\033[0m\n";

    foreach ($GLOBALS['wp_hooks']['plugins_loaded'] ?? [] as $callback) {
        $callback();
    }
    $check(isset($GLOBALS['wp_hooks']['get_canonical_url']), "Canonical guard hooks 'get_canonical_url', the filter WordPress core applies");
    $check(!isset($GLOBALS['wp_hooks']['wp_get_canonical_url']), "No callback on the non-existent 'wp_get_canonical_url' filter");
    $check(isset($GLOBALS['wp_hooks']['wp_robots'], $GLOBALS['wp_hooks']['wp_redirect']), 'Robots and redirect guards are registered');

    // Robots
    wp_reset(['blog_public' => '0', 'front_page' => true]);
    $robots = WpSeoGuard::guardRobotsMeta(['noindex' => true, 'nofollow' => true]);
    $check($robots === ['noindex' => true, 'nofollow' => true], 'Staging ("Discourage search engines") keeps its noindex on the homepage', json_encode($robots));

    wp_reset(['front_page' => true]);
    $robots = WpSeoGuard::guardRobotsMeta(['noindex' => true, 'max-image-preview' => 'large']);
    $check(empty($robots['noindex']) && !empty($robots['index']) && $GLOBALS['wp_logs'] !== [], 'Accidental noindex on a live homepage is neutralized and logged', json_encode($robots));

    wp_reset();
    $robots = WpSeoGuard::guardRobotsMeta(['noindex' => true]);
    $check($robots === ['noindex' => true], 'noindex on other pages is left alone', json_encode($robots));

    // Canonical URLs
    wp_reset();
    $url = WpSeoGuard::guardCanonicalUrl('http://example.com/services?paged=2&filter=seo#top');
    $check($url === 'https://example.com/services/?paged=2&filter=seo#top', 'HTTPS site: canonical upgraded to HTTPS with query string and fragment intact', $url);

    wp_reset(['trailing_slashes' => false]);
    $url = WpSeoGuard::guardCanonicalUrl('https://example.com/services/');
    $check($url === 'https://example.com/services', 'Trailing slash follows a permalink structure without slash (no redirecting canonical)', $url);

    wp_reset(['ssl' => false, 'home_url' => 'http://example.local']);
    $url = WpSeoGuard::guardCanonicalUrl('http://example.local/services/');
    $check($url === 'http://example.local/services/', 'HTTP-only site: canonical is not pointed at a missing HTTPS version', $url);

    wp_reset();
    $check(WpSeoGuard::guardCanonicalUrl('https://example.com/sitemap.xml') === 'https://example.com/sitemap.xml', 'File URLs keep their extension without a trailing slash');
    $check(WpSeoGuard::guardCanonicalUrl(false) === false, 'A false canonical (unpublished post) passes through untouched');

    // Redirects
    wp_reset();
    $check($redirect('https://example.com/page/') === 'wp_die 508', 'Absolute self-redirect is halted with HTTP 508');
    $check($redirect('/page/') === 'wp_die 508', 'Relative self-redirect is halted with HTTP 508');
    $check($redirect('https://EXAMPLE.com:443/page/#section') === 'wp_die 508', 'Self-redirect is detected despite host case, default port and fragment');
    $check($redirect('https://example.com/other-page/') === 'kept', 'Redirect to another URL is untouched');
    $check($redirect('https://example.com/page') === 'kept', 'Trailing-slash redirect to a different URL is untouched');

    wp_reset(['ssl' => false]);
    $check($redirect('https://example.com/page/') === 'kept', 'HTTP to HTTPS upgrade is not mistaken for a loop');

    wp_reset(['ssl' => false]);
    $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
    $check($redirect('https://example.com/page/') === 'wp_die 508', 'Behind a TLS proxy, an HTTPS redirect to the same URL is halted (classic is_ssl() loop)');

    if ($failures > 0) {
        echo "\033[31m$failures WP SEO Guard regression test(s) FAILED.\033[0m\n";
        exit(1);
    }
    echo "\033[32mAll WP SEO Guard regression tests passed!\033[0m\n";
}
