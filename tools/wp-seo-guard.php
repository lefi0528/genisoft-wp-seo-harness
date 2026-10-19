<?php
declare(strict_types=1);

/**
 * Plugin Name: Genisoft WP SEO Safety Guard (Harness Runtime)
 * Description: Runtime safety guard preventing accidental homepage de-indexing, insecure canonicals, and self-redirect loops during AI agent executions.
 * Version: 1.2.0
 * Author: Genisoft / Abdelmonem Lefi
 * License: MIT
 */

namespace Genisoft\WpSeoHarness;

if (!defined('ABSPATH')) {
    exit;
}

class WpSeoGuard {
    public static function init(): void {
        // 1. Guard against accidental noindex on homepage or front page
        add_filter('wp_robots', [__CLASS__, 'guardRobotsMeta'], 9999);

        // 2. Normalize canonical URLs. WordPress core applies 'get_canonical_url' inside wp_get_canonical_url().
        add_filter('get_canonical_url', [__CLASS__, 'guardCanonicalUrl'], 9999);

        // 3. Halt redirects that point back to the requested URL (they can only loop)
        add_filter('wp_redirect', [__CLASS__, 'guardSelfRedirect'], 9999, 2);
    }

    public static function guardRobotsMeta(array $robots): array {
        // "Discourage search engines" (blog_public = 0) is a deliberate site-wide noindex, e.g. on staging: never override it.
        if (!get_option('blog_public')) {
            return $robots;
        }

        // Strict invariant: Front page or blog index must never receive noindex unless explicitly allowed
        $allowFrontNoindex = defined('GENISOFT_ALLOW_FRONT_NOINDEX') && GENISOFT_ALLOW_FRONT_NOINDEX;
        if ((is_front_page() || is_home()) && !empty($robots['noindex']) && !$allowFrontNoindex) {
            unset($robots['noindex']);
            $robots['index'] = true;
            error_log('[GENISOFT HARNESS ALERT] Attempted noindex on homepage was INTERCEPTED and NEUTRALIZED.');
        }
        return $robots;
    }

    /**
     * Upgrades canonical URLs to HTTPS when the site runs on HTTPS and aligns the trailing slash with the
     * permalink structure, so the canonical never points to a redirecting URL. Query strings and fragments are preserved.
     *
     * @param string|false $canonical_url
     * @return string|false
     */
    public static function guardCanonicalUrl($canonical_url) {
        if (!is_string($canonical_url) || $canonical_url === '') {
            return $canonical_url;
        }

        // Invariant: Canonicals must use HTTPS on an HTTPS site
        if (str_starts_with($canonical_url, 'http://') && self::siteUsesHttps()) {
            $canonical_url = 'https://' . substr($canonical_url, 7);
        }

        $parsed = wp_parse_url($canonical_url);
        if (!is_array($parsed) || !isset($parsed['host'], $parsed['path'])) {
            return $canonical_url;
        }

        $path = $parsed['path'];
        // Follow the permalink structure (unless the path has a file extension like .html, .xml, .php)
        if ($path !== '/' && !pathinfo($path, PATHINFO_EXTENSION)) {
            $path = user_trailingslashit($path);
        }

        // Reconstruct canonical URL safely (preserving query parameters and fragments)
        $scheme = $parsed['scheme'] ?? 'https';
        $port   = isset($parsed['port']) ? ':' . $parsed['port'] : '';
        $query  = isset($parsed['query']) && $parsed['query'] !== '' ? '?' . $parsed['query'] : '';
        $frag   = isset($parsed['fragment']) && $parsed['fragment'] !== '' ? '#' . $parsed['fragment'] : '';

        return $scheme . '://' . $parsed['host'] . $port . $path . $query . $frag;
    }

    /**
     * A redirect whose target is the requested URL can only loop. Multi-hop loops (A -> B -> A) span several
     * requests and are not visible from a single one: catch them with an external crawl (curl -IL --max-redirs 5).
     *
     * @param string|false $location
     * @param int $status
     * @return string|false
     */
    public static function guardSelfRedirect($location, $status = 302) {
        if (!is_string($location) || $location === '' || !isset($_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI'])) {
            return $location;
        }

        $scheme  = self::requestScheme();
        $origin  = $scheme . '://' . $_SERVER['HTTP_HOST'];
        // Resolve like a browser: //host/path keeps the request scheme, /path keeps the request origin
        $target  = $location;
        if (str_starts_with($location, '//')) {
            $target = $scheme . ':' . $location;
        } elseif (str_starts_with($location, '/')) {
            $target = $origin . $location;
        }
        $current = $origin . $_SERVER['REQUEST_URI'];

        if (self::normalizeUrl($target) !== self::normalizeUrl($current)) {
            return $location;
        }

        error_log('[GENISOFT HARNESS ALERT] Self-redirect to ' . $location . ' (HTTP ' . $status . ') HALTED: it would loop forever.');
        wp_die(
            '<h1>[GENISOFT HARNESS ERROR] Redirect Loop Detected</h1>' .
            '<p>This request redirects to itself. Halting request to prevent an infinite redirect loop.</p>',
            'SEO Safety Guard Halt',
            ['response' => 508]
        );

        return $location;
    }

    private static function siteUsesHttps(): bool {
        return is_ssl() || str_starts_with((string) home_url('/'), 'https://');
    }

    private static function requestScheme(): string {
        // Behind a TLS-terminating proxy is_ssl() is false while the visitor is on HTTPS: the classic redirect loop.
        $forwarded = isset($_SERVER['HTTP_X_FORWARDED_PROTO']) ? strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) : '';
        return is_ssl() || $forwarded === 'https' ? 'https' : 'http';
    }

    private static function normalizeUrl(string $url): string {
        $parts = wp_parse_url($url);
        if (!is_array($parts) || !isset($parts['host'])) {
            return $url;
        }

        $scheme = strtolower($parts['scheme'] ?? 'http');
        $port   = $parts['port'] ?? null;
        $port   = ($port === null || ($scheme === 'http' && $port === 80) || ($scheme === 'https' && $port === 443)) ? '' : ':' . $port;
        $query  = isset($parts['query']) && $parts['query'] !== '' ? '?' . $parts['query'] : '';

        // Fragments are never sent to the server, so they cannot distinguish two requests
        return $scheme . '://' . strtolower($parts['host']) . $port . ($parts['path'] ?? '/') . $query;
    }
}

add_action('plugins_loaded', [WpSeoGuard::class, 'init']);
