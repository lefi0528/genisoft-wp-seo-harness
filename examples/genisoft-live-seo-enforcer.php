<?php
declare(strict_types=1);

/**
 * Plugin Name: Genisoft Live SEO & Knowledge Graph Enforcer
 * Description: Implements complete Schema.org Knowledge Graph, Open Graph meta tags, and robots directives with Transient Caching for https://www.genisoft.fr/agence-web/
 * Version: 1.1.0
 * Author: Genisoft / Abdelmonem Lefi
 * License: MIT
 */

namespace Genisoft\LiveSeo;

if (!defined('ABSPATH')) {
    exit;
}

class LiveSeoEnforcer {
    private const CACHE_GROUP = 'genisoft_seo';
    private const CACHE_KEY_SCHEMA = 'schema_graph_agence_web';
    private const CACHE_TTL = 43200; // 12 hours

    public static function init(): void {
        add_action('wp_head', [__CLASS__, 'injectMetaAndSocialTags'], 1);
        add_action('wp_head', [__CLASS__, 'injectSchemaKnowledgeGraph'], 2);
        add_filter('wp_robots', [__CLASS__, 'enhanceRobotsDirectives'], 99);
        add_filter('pre_get_document_title', [__CLASS__, 'optimizeDocumentTitle'], 99);
    }

    public static function optimizeDocumentTitle(string $title): string {
        if (is_page('agence-web') || (isset($_SERVER['REQUEST_URI']) && str_contains((string)$_SERVER['REQUEST_URI'], '/agence-web/'))) {
            return 'Agence web Grenoble spécialisée PrestaShop et SEO | Genisoft';
        }
        return $title;
    }

    public static function enhanceRobotsDirectives(array $robots): array {
        $robots['max-image-preview'] = 'large';
        $robots['max-snippet'] = -1;
        $robots['max-video-preview'] = -1;
        return $robots;
    }

    public static function injectMetaAndSocialTags(): void {
        if (!is_page('agence-web') && !(isset($_SERVER['REQUEST_URI']) && str_contains((string)$_SERVER['REQUEST_URI'], '/agence-web/'))) {
            return;
        }

        $metaDesc = "Agence web à Grenoble spécialisée dans la création de sites e-commerce PrestaShop, WordPress et le référencement naturel (SEO). Boostez votre visibilité et votre chiffre d'affaires avec Genisoft.";
        $pageUrl = "https://www.genisoft.fr/agence-web/";
        $title = "Agence web Grenoble spécialisée PrestaShop et SEO | Genisoft";
        $ogImage = "https://www.genisoft.fr/wp-content/uploads/2023/agence-web-grenoble.jpg";

        echo "\n<!-- Genisoft SEO Harness: Meta & Social Graph -->\n";
        echo '<meta name="description" content="' . esc_attr($metaDesc) . '" />' . "\n";
        echo '<meta property="og:locale" content="fr_FR" />' . "\n";
        echo '<meta property="og:type" content="website" />' . "\n";
        echo '<meta property="og:title" content="' . esc_attr($title) . '" />' . "\n";
        echo '<meta property="og:description" content="' . esc_attr($metaDesc) . '" />' . "\n";
        echo '<meta property="og:url" content="' . esc_url($pageUrl) . '" />' . "\n";
        echo '<meta property="og:site_name" content="Genisoft" />' . "\n";
        echo '<meta property="og:image" content="' . esc_url($ogImage) . '" />' . "\n";
        echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr($title) . '" />' . "\n";
        echo '<meta name="twitter:description" content="' . esc_attr($metaDesc) . '" />' . "\n";
    }

    public static function injectSchemaKnowledgeGraph(): void {
        if (!is_page('agence-web') && !(isset($_SERVER['REQUEST_URI']) && str_contains((string)$_SERVER['REQUEST_URI'], '/agence-web/'))) {
            return;
        }

        // Performance Optimization: Check Object Cache / Transient first
        $cachedJsonLd = function_exists('wp_cache_get') ? wp_cache_get(self::CACHE_KEY_SCHEMA, self::CACHE_GROUP) : false;
        if ($cachedJsonLd !== false && is_string($cachedJsonLd)) {
            echo "\n<!-- Genisoft SEO Harness: Schema.org Knowledge Graph (Cached) -->\n";
            echo '<script type="application/ld+json">' . "\n";
            echo $cachedJsonLd;
            echo "\n</script>\n";
            return;
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Organization',
                    '@id' => 'https://www.genisoft.fr/#organization',
                    'name' => 'Genisoft',
                    'url' => 'https://www.genisoft.fr/',
                    'logo' => 'https://www.genisoft.fr/wp-content/uploads/2023/logo.png',
                    'address' => [
                        '@type' => 'PostalAddress',
                        'addressLocality' => 'Grenoble',
                        'postalCode' => '38000',
                        'addressCountry' => 'FR',
                    ],
                    'sameAs' => [
                        'https://github.com/lefi0528',
                    ],
                ],
                [
                    '@type' => 'WebSite',
                    '@id' => 'https://www.genisoft.fr/#website',
                    'url' => 'https://www.genisoft.fr/',
                    'name' => 'Genisoft',
                    'publisher' => [
                        '@id' => 'https://www.genisoft.fr/#organization',
                    ],
                ],
                [
                    '@type' => 'WebPage',
                    '@id' => 'https://www.genisoft.fr/agence-web/#webpage',
                    'url' => 'https://www.genisoft.fr/agence-web/',
                    'name' => 'Agence web Grenoble spécialisée PrestaShop et SEO | Genisoft',
                    'isPartOf' => [
                        '@id' => 'https://www.genisoft.fr/#website',
                    ],
                    'about' => [
                        '@id' => 'https://www.genisoft.fr/#organization',
                    ],
                    'description' => "Agence web à Grenoble spécialisée dans la création de sites e-commerce PrestaShop, WordPress et le référencement naturel (SEO).",
                ],
                [
                    '@type' => 'BreadcrumbList',
                    '@id' => 'https://www.genisoft.fr/agence-web/#breadcrumb',
                    'itemListElement' => [
                        [
                            '@type' => 'ListItem',
                            'position' => 1,
                            'name' => 'Accueil',
                            'item' => 'https://www.genisoft.fr/',
                        ],
                        [
                            '@type' => 'ListItem',
                            'position' => 2,
                            'name' => 'Agence Web',
                            'item' => 'https://www.genisoft.fr/agence-web/',
                        ],
                    ],
                ],
            ],
        ];

        $jsonLd = wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if ($jsonLd !== false) {
            if (function_exists('wp_cache_set')) {
                wp_cache_set(self::CACHE_KEY_SCHEMA, $jsonLd, self::CACHE_GROUP, self::CACHE_TTL);
            }

            echo "\n<!-- Genisoft SEO Harness: Schema.org Knowledge Graph -->\n";
            echo '<script type="application/ld+json">' . "\n";
            echo $jsonLd;
            echo "\n</script>\n";
        }
    }
}

add_action('init', [LiveSeoEnforcer::class, 'init']);
