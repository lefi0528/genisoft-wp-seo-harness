<?php
declare(strict_types=1);

/**
 * Genisoft WordPress SEO Safety & Anti-Regression Verifier
 *
 * Standalone CLI scanner enforcing non-destructive SEO operations,
 * safe hook usage, canonical hygiene, and noindex safeguards.
 *
 * @author Abdelmonem Lefi (Genisoft)
 * @license MIT
 */

namespace Genisoft\WpSeoHarness;

class SeoSafetyVerifier {
    private array $violations = [];
    private array $warnings = [];
    private int $filesScanned = 0;

    public function checkContent(string $code, string $filePath = 'inline'): array {
        $violations = [];
        $warnings = [];

        // 1. Unsafe noindex injection (unconditional wp_robots filter)
        if (preg_match('#add_filter\s*\(\s*[\'"]wp_robots[\'"]\s*,.*[\'"]noindex[\'"].*true#is', $code)) {
            if (!str_contains($code, 'is_search') && !str_contains($code, 'is_404') && !str_contains($code, 'paged')) {
                $violations[] = "Unconditional 'noindex' detected in wp_robots filter. Risk of de-indexing entire site.";
            }
        }

        // 2. Core wp_robots removal from wp_head
        if (preg_match('#remove_action\s*\(\s*[\'"]wp_head[\'"]\s*,\s*[\'"]wp_robots[\'"]\s*\)#i', $code)) {
            $warnings[] = "Core wp_robots removed from wp_head. Ensure custom robots directives are properly emitted.";
        }

        // 3. Hardcoded non-HTTPS canonical URL
        if (preg_match('#<link[^>]+rel=[\'"]canonical[\'"][^>]+href=[\'"]http://[^\'"]+[\'"]#i', $code)) {
            $violations[] = "Insecure HTTP canonical URL detected. Canonical tags must always use HTTPS.";
        }

        // 4. Relative canonical tag
        if (preg_match('#<link[^>]+rel=[\'"]canonical[\'"][^>]+href=[\'"]/[^\'"]*[\'"]#i', $code)) {
            $violations[] = "Relative canonical URL detected. Search engines require absolute URLs.";
        }

        // 5. Unescaped post meta rendered in meta tags (XSS / SEO injection risk)
        if (preg_match('#<meta[^>]+content=[\'"]<\?php\s+echo\s+(?:\$_GET|\$_POST|get_post_meta)#i', $code)) {
            $violations[] = "Unescaped dynamic output inside <meta> tag. Must wrap with esc_attr().";
        }

        // 6. Direct .htaccess modification without syntax check
        if (str_contains($filePath, '.htaccess') && preg_match('#RewriteRule.*#i', $code)) {
            $warnings[] = ".htaccess modification detected. Verify rewrite rules to avoid redirect loops (Error 500).";
        }

        return ['violations' => $violations, 'warnings' => $warnings];
    }

    public function scanFile(string $path): void {
        if (!file_exists($path) || is_dir($path)) {
            return;
        }

        $this->filesScanned++;
        $content = file_get_contents($path);
        if ($content === false) {
            return;
        }
        $result = $this->checkContent($content, $path);

        foreach ($result['violations'] as $v) {
            $this->violations[] = "[$path] $v";
        }
        foreach ($result['warnings'] as $w) {
            $this->warnings[] = "[$path] $w";
        }
    }

    public function scanDirectory(string $dir): void {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
        foreach ($iterator as $file) {
            if ($file->isFile() && in_array($file->getExtension(), ['php', 'html', 'htaccess'], true)) {
                $this->scanFile($file->getPathname());
            }
        }
    }

    public function report(): int {
        echo "\n\033[1;36m=== Genisoft WordPress SEO Safety Report ===\033[0m\n";
        echo "Files scanned: {$this->filesScanned}\n";

        if (!empty($this->warnings)) {
            echo "\n\033[1;33mWarnings (" . count($this->warnings) . "):\033[0m\n";
            foreach ($this->warnings as $w) {
                echo "  [WARN] $w\n";
            }
        }

        if (!empty($this->violations)) {
            echo "\n\033[1;31mCRITICAL Violations (" . count($this->violations) . "):\033[0m\n";
            foreach ($this->violations as $v) {
                echo "  [FAIL] $v\n";
            }
            echo "\n\033[1;31m❌ Safety verification FAILED. Aborting agent session to prevent production damage.\033[0m\n";
            return 1;
        }

        echo "\n\033[1;32m✔ All SEO Safety Gates PASSED. Zero critical regressions detected.\033[0m\n";
        return 0;
    }
}

// Self-test runner
if (isset($argv[1]) && $argv[1] === '--self-test') {
    echo "\033[36mRunning SEO Safety Verifier self-tests...\033[0m\n";
    $verifier = new SeoSafetyVerifier();

    // Test 1: Catch unconditional noindex
    $badCode = "add_filter('wp_robots', function(\$r) { \$r['noindex'] = true; return \$r; });";
    $res1 = $verifier->checkContent($badCode);
    if (empty($res1['violations'])) {
        echo "\033[31mFAIL: Unconditional noindex was not flagged\033[0m\n";
        exit(1);
    }
    echo "  \033[32m✔\033[0m Unconditional noindex filter caught.\n";

    // Test 2: Catch HTTP canonical
    $badCanonical = '<link rel="canonical" href="http://example.com/mypage/" />';
    $res2 = $verifier->checkContent($badCanonical);
    if (empty($res2['violations'])) {
        echo "\033[31mFAIL: Insecure HTTP canonical was not flagged\033[0m\n";
        exit(1);
    }
    echo "  \033[32m✔\033[0m Insecure HTTP canonical tag caught.\n";

    // Test 3: Catch unescaped meta
    $badMeta = '<meta name="description" content="<?php echo $_GET[\'search\']; ?>" />';
    $res3 = $verifier->checkContent($badMeta);
    if (empty($res3['violations'])) {
        echo "\033[31mFAIL: Unescaped meta injection was not flagged\033[0m\n";
        exit(1);
    }
    echo "  \033[32m✔\033[0m Unescaped meta tag XSS/SEO injection caught.\n";

    echo "\033[32mAll SEO Safety Verifier self-tests passed successfully!\033[0m\n\n";
    exit(0);
}

// CLI entry point
$target = $argv[1] ?? '.';
$verifier = new SeoSafetyVerifier();

if (is_dir($target)) {
    $verifier->scanDirectory($target);
} elseif (file_exists($target)) {
    $verifier->scanFile($target);
} else {
    echo "Target '$target' not found.\n";
    exit(1);
}

exit($verifier->report());
