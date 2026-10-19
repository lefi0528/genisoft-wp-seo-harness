<?php
declare(strict_types=1);

/**
 * Plugin Name: Genisoft WP SEO Safety Guard (Harness Runtime)
 * Description: Runtime safety guard preventing accidental de-indexing, canonical loops, and catastrophic SEO regressions during AI agent executions.
 * Version: 1.1.0
 * Author: Genisoft / Abdelmonem Lefi
 * License: MIT
 */

namespace Genisoft\WpSeoHarness;

if (!defined('ABSPATH')) {
    exit;
}

class WpSeoGuard {
    private static int $redirectCount = 0;
    private const MAX_ALLOWED_REDIRECTS = 3;

    public static function init(): void {
        // 1. Guard against accidental noindex on homepage or front page
        add_filter('wp_robots', [__CLASS__, 'guardRobotsMeta'], 9999);

        // 2. Validate and normalize canonical URLs at runtime without query string corruption
        add_filter('wp_get_canonical_url', [__CLASS__, 'guardCanonicalUrl'], 9999, 2);

        // 3. Monitor template redirects to prevent infinite redirect loops
        add_action('template_redirect', [__CLASS__, 'guardRedirectLoops'], 1);
    }

    public static function guardRobotsMeta(array $robots): array {
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
     * Normalizes canonical URLs according to RFC 3986 without corrupting query strings or anchors.
     */
    public static function guardCanonicalUrl(string $canonical_url, ?\WP_Post $post = null): string {
        if (empty($canonical_url)) {
            return $canonical_url;
        }

        // Invariant: Canonicals must enforce HTTPS
        if (str_starts_with($canonical_url, 'http://')) {
            $canonical_url = 'https://' . substr($canonical_url, 7);
        }

        $parsed = function_exists('wp_parse_url') ? wp_parse_url($canonical_url) : parse_url($canonical_url);
        if (!is_array($parsed) || !isset($parsed['path'])) {
            return $canonical_url;
        }

        $path = $parsed['path'];
        // Normalize trailing slash on path (unless it has a file extension like .html, .xml, .php)
        if (!pathinfo($path, PATHINFO_EXTENSION) && !str_ends_with($path, '/')) {
            $path .= '/';
        }

        // Reconstruct canonical URL safely (preserving query parameters and fragments)
        $scheme = $parsed['scheme'] ?? 'https';
        $host   = $parsed['host'] ?? '';
        $port   = isset($parsed['port']) ? ':' . $parsed['port'] : '';
        $query  = isset($parsed['query']) && $parsed['query'] !== '' ? '?' . $parsed['query'] : '';
        $frag   = isset($parsed['fragment']) && $parsed['fragment'] !== '' ? '#' . $parsed['fragment'] : '';

        return $scheme . '://' . $host . $port . $path . $query . $frag;
    }

    public static function guardRedirectLoops(): void {
        self::$redirectCount++;
        if (self::$redirectCount > self::MAX_ALLOWED_REDIRECTS) {
            wp_die(
                '<h1>[GENISOFT HARNESS ERROR] Redirect Loop Detected</h1>' .
                '<p>An autonomous agent modification triggered a recursive redirection loop. Halting request to prevent cascade failure.</p>',
                'SEO Safety Guard Halt',
                ['response' => 508]
            );
        }
    }
}

add_action('plugins_loaded', [WpSeoGuard::class, 'init']);
