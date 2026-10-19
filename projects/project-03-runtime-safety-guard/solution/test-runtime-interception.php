<?php
declare(strict_types=1);

// Simulation of wp-seo-guard.php runtime interception

function mock_wp_robots_filter(array $robots): array {
    // Attempted dangerous homepage noindex
    $robots['noindex'] = true;

    // wp-seo-guard interception simulation:
    $is_front_page = true;
    if ($is_front_page && !empty($robots['noindex'])) {
        unset($robots['noindex']);
        $robots['index'] = true;
    }
    return $robots;
}

function mock_canonical_guard(string $url): string {
    if (str_starts_with($url, 'http://')) {
        $url = 'https://' . substr($url, 7);
    }
    $parsed = parse_url($url);
    if (!is_array($parsed) || !isset($parsed['path'])) {
        return $url;
    }
    $path = $parsed['path'];
    if (!pathinfo($path, PATHINFO_EXTENSION) && !str_ends_with($path, '/')) {
        $path .= '/';
    }
    $scheme = $parsed['scheme'] ?? 'https';
    $host   = $parsed['host'] ?? '';
    $port   = isset($parsed['port']) ? ':' . $parsed['port'] : '';
    $query  = isset($parsed['query']) && $parsed['query'] !== '' ? '?' . $parsed['query'] : '';
    $frag   = isset($parsed['fragment']) && $parsed['fragment'] !== '' ? '#' . $parsed['fragment'] : '';

    return $scheme . '://' . $host . $port . $path . $query . $frag;
}

echo "Running Genisoft Runtime Safety Guard Simulations (v1.1.0)...
";

// Sim 1
$r = mock_wp_robots_filter([]);
if (empty($r['noindex']) && !empty($r['index'])) {
    echo "  ✔ Simulation 1: Homepage noindex neutralized successfully.
";
} else {
    echo "  ❌ Simulation 1 failed.
";
    exit(1);
}

// Sim 2: Preserves query string without corrupting trailing slash
$c = mock_canonical_guard('http://example.com/services?paged=2&filter=seo');
if ($c === 'https://example.com/services/?paged=2&filter=seo') {
    echo "  ✔ Simulation 2: Insecure canonical normalized to HTTPS with query string intact.
";
} else {
    echo "  ❌ Simulation 2 failed (Output was: $c).
";
    exit(1);
}

echo "All runtime simulations passed successfully!
";
