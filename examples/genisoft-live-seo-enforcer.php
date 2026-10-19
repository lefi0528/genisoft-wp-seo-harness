<?php
declare(strict_types=1);

/**
 * Plugin Name: Genisoft Live SEO & Knowledge Graph Enforcer
 * Description: Example plugin: connected Schema.org Knowledge Graph, Open Graph meta tags, and robots directives for the "agence-web" page. Stays silent when an SEO plugin (Yoast, Rank Math, SEOPress, AIOSEO) already outputs them.
 * Version: 1.2.0
 * Author: Genisoft / Abdelmonem Lefi
 * License: MIT
 */

namespace Genisoft\LiveSeo;

if (!defined('ABSPATH')) {
    exit;
}

class LiveSeoEnforcer {
    private const PAGE_SLUG = 'agence-web';
    private const TITLE = 'Agence web Grenoble spécialisée PrestaShop et SEO | Genisoft';
    private const DESCRIPTION = "Agence web à Grenoble spécialisée dans la création de sites e-commerce PrestaShop, WordPress et le référencement naturel (SEO). Boostez votre visibilité et votre chiffre d'affaires avec Genisoft.";
    private const CACHE_GROUP = 'genisoft_seo';
    private const CACHE_KEY_SCHEMA = 'schema_graph_agence_web';
    private const CACHE_TTL = 43200; // 12 hours

    public static function init(): void {
        add_filter('wp_robots', [__CLASS__, 'enhanceRobotsDirectives'], 99);

        // An SEO plugin already prints the title, meta tags and its own graph: a second copy would conflict
        if (self::seoPluginActive()) {
            return;
        }
        add_action('wp_head', [__CLASS__, 'injectMetaAndSocialTags'], 1);
        add_action('wp_head', [__CLASS__, 'injectSchemaKnowledgeGraph'], 2);
        add_filter('pre_get_document_title', [__CLASS__, 'optimizeDocumentTitle'], 99);
    }

    public static function seoPluginActive(): bool {
        return defined('WPSEO_VERSION') || defined('RANK_MATH_VERSION') || defined('SEOPRESS_VERSION') || defined('AIOSEO_VERSION');
    }

    public static function optimizeDocumentTitle(string $title): string {
        return is_page(self::PAGE_SLUG) ? self::TITLE : $title;
    }

    public static function enhanceRobotsDirectives(array $robots): array {
        $robots['max-image-preview'] = 'large';
        $robots['max-snippet'] = -1;
        $robots['max-video-preview'] = -1;
        return $robots;
    }

    public static function injectMetaAndSocialTags(): void {
        if (!is_page(self::PAGE_SLUG)) {
            return;
        }

        $image = self::pageImageUrl();

        echo "\n<!-- Genisoft SEO Harness: Meta & Social Graph -->\n";
        echo '<meta name="description" content="' . esc_attr(self::DESCRIPTION) . '" />' . "\n";
        echo '<meta property="og:locale" content="fr_FR" />' . "\n";
        echo '<meta property="og:type" content="website" />' . "\n";
        echo '<meta property="og:title" content="' . esc_attr(self::TITLE) . '" />' . "\n";
        echo '<meta property="og:description" content="' . esc_attr(self::DESCRIPTION) . '" />' . "\n";
        echo '<meta property="og:url" content="' . esc_url((string) get_permalink()) . '" />' . "\n";
        echo '<meta property="og:site_name" content="Genisoft" />' . "\n";
        // Only reference an image that exists: the page's featured image, or the site icon
        if ($image !== '') {
            echo '<meta property="og:image" content="' . esc_url($image) . '" />' . "\n";
        }
        echo '<meta name="twitter:card" content="' . esc_attr($image !== '' ? 'summary_large_image' : 'summary') . '" />' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr(self::TITLE) . '" />' . "\n";
        echo '<meta name="twitter:description" content="' . esc_attr(self::DESCRIPTION) . '" />' . "\n";
    }

    public static function injectSchemaKnowledgeGraph(): void {
        if (!is_page(self::PAGE_SLUG)) {
            return;
        }

        // Performance Optimization: Check Object Cache first
        $jsonLd = wp_cache_get(self::CACHE_KEY_SCHEMA, self::CACHE_GROUP);
        if (!is_string($jsonLd)) {
            $jsonLd = wp_json_encode(self::buildSchemaGraph(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            if ($jsonLd === false) {
                return;
            }
            wp_cache_set(self::CACHE_KEY_SCHEMA, $jsonLd, self::CACHE_GROUP, self::CACHE_TTL);
        }

        echo "\n<!-- Genisoft SEO Harness: Schema.org Knowledge Graph -->\n";
        echo '<script type="application/ld+json">' . "\n";
        echo $jsonLd;
        echo "\n</script>\n";
    }

    /** Connected graph: every entity has an @id built from the site's own URLs, and every reference resolves */
    public static function buildSchemaGraph(): array {
        $home = home_url('/');
        $pageUrl = (string) get_permalink();
        $logo = self::logoUrl();

        $organization = [
            '@type' => 'Organization',
            '@id' => $home . '#organization',
            'name' => 'Genisoft',
            'url' => $home,
            'address' => [
                '@type' => 'PostalAddress',
                'addressLocality' => 'Grenoble',
                'postalCode' => '38000',
                'addressCountry' => 'FR',
            ],
            'sameAs' => [
                'https://github.com/lefi0528',
                'https://www.fiverr.com/genisoft0528',
            ],
        ];
        if ($logo !== '') {
            $organization['logo'] = $logo;
        }

        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                $organization,
                [
                    '@type' => 'WebSite',
                    '@id' => $home . '#website',
                    'url' => $home,
                    'name' => 'Genisoft',
                    'publisher' => ['@id' => $home . '#organization'],
                ],
                [
                    '@type' => 'WebPage',
                    '@id' => $pageUrl . '#webpage',
                    'url' => $pageUrl,
                    'name' => self::TITLE,
                    'description' => self::DESCRIPTION,
                    'isPartOf' => ['@id' => $home . '#website'],
                    'about' => ['@id' => $home . '#organization'],
                    'breadcrumb' => ['@id' => $pageUrl . '#breadcrumb'],
                ],
                [
                    '@type' => 'BreadcrumbList',
                    '@id' => $pageUrl . '#breadcrumb',
                    'itemListElement' => [
                        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Accueil', 'item' => $home],
                        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Agence Web', 'item' => $pageUrl],
                    ],
                ],
            ],
        ];
    }

    private static function pageImageUrl(): string {
        $image = get_the_post_thumbnail_url(null, 'full');
        return is_string($image) && $image !== '' ? $image : (string) get_site_icon_url(512);
    }

    private static function logoUrl(): string {
        $logoId = (int) get_theme_mod('custom_logo');
        $logo = $logoId > 0 ? wp_get_attachment_image_url($logoId, 'full') : false;
        return is_string($logo) && $logo !== '' ? $logo : (string) get_site_icon_url(512);
    }
}

add_action('init', [LiveSeoEnforcer::class, 'init']);
