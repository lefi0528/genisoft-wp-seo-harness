<?php
declare(strict_types=1);

/**
 * Genisoft WordPress SEO Safety & Anti-Regression Verifier
 *
 * Standalone CLI scanner enforcing non-destructive SEO operations,
 * safe hook usage, canonical hygiene, and noindex safeguards.
 *
 * PHP sources are analyzed with the PHP tokenizer: comments and string fixtures never
 * trigger findings, attribute order does not matter, and a noindex is only accepted when
 * a narrow condition guards it (is_search(), is_404(), specific IDs, slugs or templates).
 *
 * Usage: php verify-seo-safety.php <file-or-directory> [more targets...]
 *        php verify-seo-safety.php --self-test
 *
 * @author Abdelmonem Lefi (Genisoft)
 * @license MIT
 */

namespace Genisoft\WpSeoHarness;

class SeoSafetyVerifier {
    public const RULE_NOINDEX = 'SEO-NOINDEX';
    public const RULE_CANONICAL_HTTP = 'SEO-CANONICAL-HTTP';
    public const RULE_CANONICAL_RELATIVE = 'SEO-CANONICAL-RELATIVE';
    public const RULE_META_UNESCAPED = 'SEO-META-UNESCAPED';

    private const SKIPPED_DIRECTORIES = ['.git', 'node_modules', 'vendor'];
    private const SCANNED_EXTENSIONS = ['php', 'html', 'htaccess'];

    // Conditions that restrict a noindex to a narrow set of URLs
    private const NARROW_CONDITIONS = [
        'is_search', 'is_404', 'is_attachment', 'is_paged', 'is_preview', 'is_feed', 'is_comment_feed', 'is_embed',
        'is_trackback', 'is_robots', 'is_date', 'is_year', 'is_month', 'is_day', 'is_time', 'is_author', 'is_tag',
        'is_customize_preview', 'is_cart', 'is_checkout', 'is_account_page', 'is_wc_endpoint_url',
        'get_post_meta', 'get_term_meta', 'get_user_meta', 'get_field', 'get_query_var', 'has_term', 'has_tag',
        'has_category',
    ];

    // Values that identify the requested URL: comparing them with a literal targets specific URLs
    private const URL_SELECTOR_FUNCTIONS = [
        'get_the_id', 'get_queried_object_id', 'get_post_field', 'get_page_template_slug', 'get_post_type', 'get_query_var',
    ];
    private const URL_SELECTOR_PROPERTIES = ['id', 'post_name', 'post_type', 'slug', 'term_id'];

    // Conditions that are narrow only when they target specific posts, pages, terms or templates
    private const NARROW_WITH_ARGUMENTS = [
        'is_singular', 'is_single', 'is_page', 'is_tax', 'is_category', 'is_post_type_archive', 'is_page_template',
        'is_product_category', 'is_product_tag',
    ];

    private const ESCAPING_FUNCTIONS = [
        'esc_attr', 'esc_url', 'esc_html', 'esc_attr__', 'esc_html__', 'esc_attr_x', 'esc_html_x', 'esc_textarea',
        'absint', 'intval', 'floatval', 'boolval', 'number_format', 'number_format_i18n', 'sanitize_key', 'tag_escape',
    ];

    // Core wp_robots callbacks that add noindex
    private const NOINDEX_CALLBACKS = ['wp_robots_no_robots', 'wp_robots_sensitive_page'];

    private const ALLOW_NOINDEX_ANNOTATION = 'seo-harness: allow-noindex';

    private array $violations = [];
    private array $warnings = [];
    private int $filesScanned = 0;

    // State of the source being analyzed
    private string $code = '';
    /** @var \PhpToken[] Significant tokens: no whitespace, no comments */
    private array $tokens = [];
    /** @var \PhpToken[] */
    private array $comments = [];
    private array $match = [];
    private array $blockOf = [];
    private array $functions = [];
    private array $regions = [];
    private array $found = [];

    public function checkContent(string $code, string $filePath = 'inline'): array {
        $this->found = ['violations' => [], 'warnings' => []];

        if (str_contains($filePath, '.htaccess')) {
            if (preg_match('#RewriteRule#i', $code)) {
                $this->warning(null, '.htaccess modification detected. Verify rewrite rules to avoid redirect loops (Error 500).');
            }
            return $this->found;
        }

        $this->analyze($code);
        $this->checkRobotsRemoval();
        $this->checkRobotsFilters();
        $this->checkRobotsHeaders();
        $this->checkOutputFragments();

        return [
            'violations' => array_values(array_unique($this->found['violations'])),
            'warnings' => array_values(array_unique($this->found['warnings'])),
        ];
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
        $directory = new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS);
        $filter = new \RecursiveCallbackFilterIterator($directory, function (\SplFileInfo $file): bool {
            return !$file->isDir() || !in_array($file->getFilename(), self::SKIPPED_DIRECTORIES, true);
        });

        $paths = [];
        foreach (new \RecursiveIteratorIterator($filter) as $file) {
            if ($file->isFile() && in_array($file->getExtension(), self::SCANNED_EXTENSIONS, true)) {
                $paths[] = $file->getPathname();
            }
        }
        sort($paths);
        foreach ($paths as $path) {
            $this->scanFile($path);
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

    /**
     * Runs every fixture of $fixturesDir and compares the violated rules with the fixture's
     * "expect: RULE[, RULE]" (or "expect: none") header.
     */
    public static function runSelfTest(string $fixturesDir): int {
        echo "\033[36mRunning SEO Safety Verifier self-tests...\033[0m\n";

        $files = is_dir($fixturesDir) ? array_values(array_filter(scandir($fixturesDir), fn($f) => preg_match('/\.(php|html)$/', $f) === 1)) : [];
        if ($files === []) {
            echo "\033[31mFAIL: No fixtures found in $fixturesDir\033[0m\n";
            return 1;
        }

        $failures = 0;
        foreach ($files as $file) {
            $content = (string) file_get_contents($fixturesDir . DIRECTORY_SEPARATOR . $file);
            if (!preg_match('/expect:\s*(none|SEO-[A-Z-]+(?:\s*,\s*SEO-[A-Z-]+)*)/', $content, $m)) {
                echo "  \033[31m✘\033[0m $file: missing 'expect:' header\n";
                $failures++;
                continue;
            }
            $expected = $m[1] === 'none' ? [] : array_values(array_filter(array_map('trim', explode(',', $m[1]))));
            sort($expected);

            $result = (new self())->checkContent($content, $file);
            $actual = self::rulesOf($result['violations']);

            if ($actual === $expected) {
                echo "  \033[32m✔\033[0m $file (" . ($expected ? implode(', ', $expected) : 'no violation') . ")\n";
                continue;
            }
            $failures++;
            echo "  \033[31m✘\033[0m $file: expected [" . implode(', ', $expected) . '], got [' . implode(', ', $actual) . "]\n";
            foreach ($result['violations'] as $v) {
                echo "      $v\n";
            }
        }

        if ($failures > 0) {
            echo "\033[31m$failures of " . count($files) . " SEO Safety Verifier self-tests FAILED.\033[0m\n\n";
            return 1;
        }
        echo "\033[32mAll " . count($files) . " SEO Safety Verifier self-tests passed successfully!\033[0m\n\n";
        return 0;
    }

    /** @return string[] Sorted unique rule IDs of the given violation messages */
    public static function rulesOf(array $violations): array {
        $rules = [];
        foreach ($violations as $v) {
            if (preg_match('/\[(SEO-[A-Z-]+)\]/', $v, $m)) {
                $rules[$m[1]] = true;
            }
        }
        $rules = array_keys($rules);
        sort($rules);
        return $rules;
    }

    // ---------------------------------------------------------------------
    // Rules
    // ---------------------------------------------------------------------

    // Core wp_robots removal from wp_head
    private function checkRobotsRemoval(): void {
        foreach ($this->findCalls('remove_action') as $call) {
            if ($this->stringValue($call['args'][0] ?? null) === 'wp_head' && $this->stringValue($call['args'][1] ?? null) === 'wp_robots') {
                $this->warning($this->tokens[$call['at']]->line, 'Core wp_robots removed from wp_head. Ensure custom robots directives are properly emitted.');
            }
        }
    }

    // noindex added through the wp_robots filter
    private function checkRobotsFilters(): void {
        foreach ($this->findCalls('add_filter') as $call) {
            $callback = $call['args'][1] ?? null;
            if ($callback === null || $this->stringValue($call['args'][0] ?? null) !== 'wp_robots') {
                continue;
            }

            $callbackName = $this->stringValue($callback);
            if ($callbackName !== null && in_array($this->normalizeName($callbackName), self::NOINDEX_CALLBACKS, true)) {
                $this->assessNoindex($call['at'], $this->guardsFor($call['at']), "core callback '$callbackName' on wp_robots");
                continue;
            }

            $registrationGuards = $this->guardsFor($call['at']);
            foreach ($this->callbackBodies($callback) as $function) {
                // A closure sees the conditions around add_filter() positionally; a named callback does not
                $extraGuards = $function['type'] === 'named' ? $registrationGuards : [];
                foreach ($this->noindexSites($function['start'], $function['end']) as [$site, $valueGuards]) {
                    $this->assessNoindex($site, array_merge($this->guardsFor($site), $valueGuards, $extraGuards), "'noindex' in wp_robots filter");
                }
            }
        }
    }

    // noindex sent as an X-Robots-Tag HTTP header
    private function checkRobotsHeaders(): void {
        foreach ($this->findCalls('header') as $call) {
            $header = $this->stringValue($call['args'][0] ?? null);
            if ($header !== null && preg_match('/^\s*x-robots-tag\s*:.*\bnoindex\b/i', $header)) {
                $this->assessNoindex($call['at'], $this->guardsFor($call['at']), 'X-Robots-Tag noindex header');
            }
        }
    }

    // Canonical, robots and escaping checks on the HTML a file outputs (templates, echo, printf, string building)
    private function checkOutputFragments(): void {
        $this->analyzeFragment($this->templateParts());

        $seen = [];
        foreach ($this->tokens as $i => $token) {
            if (!$token->is([T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE]) || !preg_match('#<(meta|link)\b#i', $token->text)) {
                continue;
            }
            [$range, $separators, $formatArguments] = $this->outputExpression($i);
            if (isset($seen[$range[0]])) {
                continue;
            }
            $seen[$range[0]] = true;

            $parts = $this->partsOf($range, $separators);
            if ($formatArguments !== null) {
                $parts = $this->applyFormatArguments($parts, $formatArguments);
            }
            $this->analyzeFragment($parts);
        }
    }

    private function analyzeFragment(array $parts): void {
        $html = '';
        $expressions = [];
        $offsets = [];
        foreach ($parts as [$kind, $value, $at]) {
            $offsets[] = [strlen($html), $at];
            if ($kind === 'text') {
                $html .= $value;
            } elseif ($kind === 'expr') {
                $html .= "\x01" . count($expressions) . "\x02";
                $expressions[] = $value;
            } else {
                // Output we cannot see, e.g. a template tag that echoes on its own
                $html .= "\x03";
            }
        }

        if (!preg_match_all('#<(meta|link)\b([^>]*)#i', $html, $tags, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            return;
        }

        foreach ($tags as $tag) {
            [$partOffset, $at] = $this->partAt($offsets, $tag[0][1]);
            $line = $this->tokens[$at]->line + substr_count(substr($html, $partOffset, $tag[0][1] - $partOffset), "\n");
            $name = strtolower($tag[1][0]);
            $attributes = $this->parseAttributes($tag[2][0]);

            if ($name === 'link') {
                $rel = strtolower($attributes['rel'] ?? '');
                if (!preg_match('/\b(canonical|alternate|prev|next)\b/', $rel)) {
                    continue;
                }
                if (preg_match('/\bcanonical\b/', $rel)) {
                    $this->checkCanonicalHref($attributes['href'] ?? null, $line);
                }
            } elseif (preg_match('/^(robots|googlebot|bingbot)$/i', $attributes['name'] ?? '') && preg_match('/\bnoindex\b/i', $attributes['content'] ?? '')) {
                $this->assessNoindex($at, $this->guardsFor($at), 'hard-coded robots meta tag with noindex', $line);
            }

            foreach ($attributes as $attribute => $value) {
                preg_match_all('/\x01(\d+)\x02/', $value, $markers);
                foreach ($markers[1] as $index) {
                    $expression = $expressions[(int) $index];
                    if (!$this->isEscaped($expression)) {
                        $this->violation(self::RULE_META_UNESCAPED, $this->tokens[$expression[0]]->line,
                            "Unescaped dynamic output `" . $this->sourceOf($expression) . "` in the '$attribute' attribute of a $name tag. Must wrap with esc_attr() (esc_url() for URLs).");
                    }
                }
            }
        }
    }

    private function checkCanonicalHref(?string $href, int $line): void {
        if ($href === null) {
            return;
        }
        $static = ltrim($href);
        $marker = strcspn($static, "\x01\x03");
        $static = substr($static, 0, $marker);

        if (stripos($static, 'http://') === 0) {
            $this->violation(self::RULE_CANONICAL_HTTP, $line, 'Insecure HTTP canonical URL detected. Canonical tags must always use HTTPS.');
        } elseif (preg_match('#^(//|/|\./|\.\./)#', $static)) {
            $this->violation(self::RULE_CANONICAL_RELATIVE, $line, 'Relative canonical URL detected. Google recommends absolute URLs and the harness requires them.');
        }
    }

    private function assessNoindex(int $site, array $guards, string $subject, ?int $line = null): void {
        foreach ($guards as [$range, $negated]) {
            if ($this->isNarrow($range, $negated)) {
                return;
            }
        }

        $line ??= $this->tokens[$site]->line;
        $message = $guards === []
            ? "Unconditional $subject. Risk of de-indexing entire site."
            : ucfirst($subject) . ' is only guarded by broad or inverted condition(s) ' . $this->describe($guards)
                . '. Risk of de-indexing whole sections: restrict it to is_search(), is_404() or specific IDs/slugs.';

        if ($this->isAnnotated($line)) {
            $this->warning($line, "$message Allowed by '" . self::ALLOW_NOINDEX_ANNOTATION . "' annotation: review it.");
            return;
        }
        $this->violation(self::RULE_NOINDEX, $line, $message);
    }

    // ---------------------------------------------------------------------
    // Condition analysis
    // ---------------------------------------------------------------------

    /**
     * True when the condition (or its negation) can only hold on a narrow set of URLs.
     * OR needs every branch to be narrow; AND needs one narrow operand.
     */
    private function isNarrow(array $range, bool $negated): bool {
        $range = $this->stripParens($range);
        if ($range[0] >= $range[1]) {
            return false;
        }

        $or = $this->splitTopLevel($range, [T_BOOLEAN_OR, T_LOGICAL_OR]);
        if (count($or) > 1) {
            // !(A || B) is !A && !B
            return $negated ? $this->anyNarrow($or, true) : $this->allNarrow($or, false);
        }
        $and = $this->splitTopLevel($range, [T_BOOLEAN_AND, T_LOGICAL_AND]);
        if (count($and) > 1) {
            // !(A && B) is !A || !B
            return $negated ? $this->allNarrow($and, true) : $this->anyNarrow($and, false);
        }
        if ($this->tokens[$range[0]]->text === '!') {
            return $this->isNarrow([$range[0] + 1, $range[1]], !$negated);
        }
        return !$negated && $this->isNarrowAtom($range);
    }

    private function anyNarrow(array $ranges, bool $negated): bool {
        foreach ($ranges as $range) {
            if ($this->isNarrow($range, $negated)) {
                return true;
            }
        }
        return false;
    }

    private function allNarrow(array $ranges, bool $negated): bool {
        foreach ($ranges as $range) {
            if (!$this->isNarrow($range, $negated)) {
                return false;
            }
        }
        return true;
    }

    private function isNarrowAtom(array $range): bool {
        // Equality is narrow only between a URL selector and a literal: get_the_ID() === 42, not is_admin() === false
        $equality = $this->findTopLevel($range, [T_IS_IDENTICAL, T_IS_EQUAL]);
        if ($equality !== null) {
            $left = [$range[0], $equality];
            $right = [$equality + 1, $range[1]];
            return ($this->isUrlSelector($left) && $this->isIdentifierLiteral($right))
                || ($this->isUrlSelector($right) && $this->isIdentifierLiteral($left));
        }
        $name = $this->callName($range[0]);
        if ($name === null || ($this->match[$range[0] + 1] ?? null) !== $range[1] - 1) {
            return false;
        }
        if (in_array($name, self::NARROW_CONDITIONS, true)) {
            return true;
        }
        if ($name === 'in_array') {
            $arguments = $this->splitTopLevel([$range[0] + 2, $range[1] - 1], [',']);
            return $this->isUrlSelector($arguments[0]);
        }
        $hasArguments = $range[1] - 1 > $range[0] + 2;
        return $hasArguments && in_array($name, self::NARROW_WITH_ARGUMENTS, true);
    }

    // get_the_ID(), get_post_field('post_name'), $post->ID, get_queried_object()->term_id...
    private function isUrlSelector(array $range): bool {
        $range = $this->stripParens($range);
        if ($range[0] >= $range[1]) {
            return false;
        }
        $name = $this->callName($range[0]);
        if ($name !== null && ($this->match[$range[0] + 1] ?? null) === $range[1] - 1) {
            return in_array($name, self::URL_SELECTOR_FUNCTIONS, true);
        }
        $property = $this->tokens[$range[1] - 1];
        $arrow = $range[1] - $range[0] >= 3 ? $this->tokens[$range[1] - 2] : null;
        return $arrow !== null && $arrow->is([T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR]) && $property->is(T_STRING)
            && in_array(strtolower($property->text), self::URL_SELECTOR_PROPERTIES, true);
    }

    // 42, 'thank-you', THANK_YOU_PAGE_ID or Pages::THANK_YOU (never true/false/null)
    private function isIdentifierLiteral(array $range): bool {
        $range = $this->stripParens($range);
        $length = $range[1] - $range[0];
        $first = $this->tokens[$range[0]] ?? null;
        if ($first === null || $length < 1) {
            return false;
        }
        if ($length === 1) {
            return $first->is(T_LNUMBER)
                || ($first->is(T_CONSTANT_ENCAPSED_STRING) && $this->unquote($first->text) !== '')
                || ($first->is(T_STRING) && !in_array(strtolower($first->text), ['true', 'false', 'null'], true));
        }
        return $length === 3 && $first->is([T_STRING, T_STATIC]) && $this->tokens[$range[0] + 1]->is(T_DOUBLE_COLON)
            && $this->tokens[$range[0] + 2]->is(T_STRING);
    }

    /** @return array<array{0: array, 1: bool}> Conditions under which token $p runs, as [range, negated] pairs */
    private function guardsFor(int $p): array {
        $enclosing = $this->functionsContaining($p);
        $boundary = null;
        foreach ($enclosing as $function) {
            if ($function['type'] === 'named') {
                $boundary = $function;
                break;
            }
        }
        if ($boundary === null) {
            $enclosing[] = ['type' => 'file', 'decl' => -1, 'start' => 0, 'end' => count($this->tokens)];
        }

        $guards = [];
        foreach ($this->regions as $region) {
            if ($p >= $region['start'] && $p < $region['end'] && ($boundary === null || $region['start'] >= $boundary['start'])) {
                array_push($guards, ...$region['guards']);
            }
        }

        // Early exits before $p: `if (!is_search()) { return $robots; }` guards what follows with is_search()
        foreach ($enclosing as $function) {
            foreach ($this->regions as $region) {
                if (!$region['first'] || $region['end'] > $p || $region['start'] < $function['start']
                    || $this->innermostFunction($region['if']) !== $function['decl']
                    || !$this->blockEncloses($this->blockOf[$region['if']], $p)
                    || !$this->exitsWithin($region, $function['decl'])) {
                    continue;
                }
                $guards[] = [$region['condition'], true];
            }
            if ($function['type'] === 'named') {
                break;
            }
        }
        return $guards;
    }

    private function describe(array $guards): string {
        return implode(', ', array_map(
            fn($guard) => '`' . ($guard[1] ? '!(' . $this->sourceOf($guard[0]) . ')' : $this->sourceOf($guard[0])) . '`',
            $guards
        ));
    }

    private function isAnnotated(int $line): bool {
        foreach ($this->comments as $comment) {
            $last = $comment->line + substr_count($comment->text, "\n");
            if ($last >= $line - 1 && $comment->line <= $line && str_contains(strtolower($comment->text), self::ALLOW_NOINDEX_ANNOTATION)) {
                return true;
            }
        }
        return false;
    }

    // ---------------------------------------------------------------------
    // wp_robots callbacks
    // ---------------------------------------------------------------------

    private function callbackBodies(array $range): array {
        $declaration = $this->tokens[$range[0]]->is(T_STATIC) ? $range[0] + 1 : $range[0];
        if (($this->tokens[$declaration] ?? null)?->is([T_FUNCTION, T_FN])) {
            return array_values(array_filter($this->functions, fn($f) => $f['decl'] === $declaration));
        }

        $line = $this->tokens[$range[0]]->line;
        $name = $this->callbackName($range);
        if ($name === null) {
            $this->warning($line, 'wp_robots callback cannot be resolved statically. Review it manually.');
            return [];
        }
        $bodies = array_values(array_filter($this->functions, fn($f) => $f['type'] === 'named' && $f['name'] === $name));
        if ($bodies === []) {
            $this->warning($line, "wp_robots callback '$name' is defined in another file. Scan that file too.");
        }
        return $bodies;
    }

    private function callbackName(array $range): ?string {
        $name = $this->stringValue($range);
        if ($name !== null) {
            return $this->normalizeName($name);
        }

        // [$object, 'method'] or array($object, 'method')
        $first = $this->tokens[$range[0]];
        $open = null;
        if ($first->text === '[') {
            $open = $range[0];
        } elseif ($first->is(T_ARRAY)) {
            $open = $range[0] + 1;
        }
        if ($open !== null && isset($this->match[$open])) {
            $items = $this->splitTopLevel([$open + 1, $this->match[$open]], [',']);
            $method = isset($items[1]) ? $this->stringValue($items[1]) : null;
            return $method === null ? null : strtolower($method);
        }

        // First-class callable syntax: theme_robots(...)
        $callee = $this->callName($range[0]);
        return $callee !== null && ($this->tokens[$range[0] + 2] ?? null)?->is(T_ELLIPSIS) ? $callee : null;
    }

    /** @return array<array{0: int, 1: array}> Token positions setting noindex, with the guards carried by the assigned value */
    private function noindexSites(int $start, int $end): array {
        $sites = [];
        for ($i = $start; $i < $end; $i++) {
            if (in_array($this->callName($i), self::NOINDEX_CALLBACKS, true)) {
                $sites[] = [$i, []];
                continue;
            }

            $token = $this->tokens[$i];
            if (!$token->is(T_CONSTANT_ENCAPSED_STRING) || strtolower($this->unquote($token->text)) !== 'noindex') {
                continue;
            }

            $valueStart = null;
            if (($this->tokens[$i - 1] ?? null)?->text === '[' && ($this->tokens[$i + 1] ?? null)?->text === ']'
                && ($this->tokens[$i + 2] ?? null)?->is(['=', T_COALESCE_EQUAL])) {
                $valueStart = $i + 3;
            } elseif (($this->tokens[$i + 1] ?? null)?->is(T_DOUBLE_ARROW)) {
                $valueStart = $i + 2;
            }
            if ($valueStart === null) {
                continue;
            }

            [$setsNoindex, $guards] = $this->classifyValue([$valueStart, $this->expressionEnd($valueStart, $end)]);
            if ($setsNoindex) {
                $sites[] = [$i, $guards];
            }
        }
        return $sites;
    }

    /** @return array{0: bool, 1: array} Whether the value can enable noindex, and the condition it carries */
    private function classifyValue(array $range): array {
        $range = $this->stripParens($range);
        $literal = $this->literalTruth($range);
        if ($literal !== null) {
            return [$literal, []];
        }

        $question = $this->findTopLevel($range, ['?']);
        $colon = $question === null ? null : $this->findTopLevel([$question + 1, $range[1]], [':']);
        if ($question !== null && $colon !== null && $colon > $question + 1) {
            $condition = [$range[0], $question];
            $whenTrue = $this->literalTruth([$question + 1, $colon]);
            $whenFalse = $this->literalTruth([$colon + 1, $range[1]]);
            if ($whenTrue === true && $whenFalse === false) {
                return [true, [[$condition, false]]];
            }
            if ($whenTrue === false && $whenFalse === true) {
                return [true, [[$condition, true]]];
            }
        }
        return [true, [[$range, false]]];
    }

    private function literalTruth(array $range): ?bool {
        $range = $this->stripParens($range);
        if ($range[1] - $range[0] !== 1) {
            return null;
        }
        $token = $this->tokens[$range[0]];
        $text = strtolower($token->text);
        if ($token->is(T_STRING)) {
            if ($text === 'true') {
                return true;
            }
            return in_array($text, ['false', 'null'], true) ? false : null;
        }
        if ($token->is([T_LNUMBER, T_DNUMBER])) {
            return (float) $text != 0;
        }
        if ($token->is(T_CONSTANT_ENCAPSED_STRING)) {
            return !in_array($this->unquote($token->text), ['', '0'], true);
        }
        return null;
    }

    // ---------------------------------------------------------------------
    // Output reconstruction
    // ---------------------------------------------------------------------

    /** HTML outside PHP tags, with `<?= ... ?>` and `<?php echo ...; ?>` blocks as expressions */
    private function templateParts(): array {
        $parts = [];
        $count = count($this->tokens);
        for ($i = 0; $i < $count; $i++) {
            $token = $this->tokens[$i];
            if ($token->is(T_INLINE_HTML)) {
                $parts[] = ['text', $token->text, $i];
                continue;
            }
            if (!$token->is([T_OPEN_TAG, T_OPEN_TAG_WITH_ECHO])) {
                continue;
            }

            $close = $i + 1;
            while ($close < $count && !$this->tokens[$close]->is(T_CLOSE_TAG)) {
                $close++;
            }
            $start = null;
            if ($token->is(T_OPEN_TAG_WITH_ECHO)) {
                $start = $i + 1;
            } elseif (($this->tokens[$i + 1] ?? null)?->is([T_ECHO, T_PRINT])) {
                $start = $i + 2;
            }
            $end = $close;
            if ($start !== null && ($this->tokens[$end - 1] ?? null)?->text === ';') {
                $end--;
            }

            if ($start !== null && $end > $start && $this->findTopLevel([$start, $end], [';']) === null) {
                array_push($parts, ...$this->partsOf([$start, $end], ['.', ',']));
            } else {
                $parts[] = ['opaque', null, $i];
            }
            $i = $close;
        }
        return $parts;
    }

    /** @return array{0: array, 1: array, 2: ?array} The concatenation around $anchor, its operand separators, and printf arguments */
    private function outputExpression(int $anchor): array {
        $j = $anchor - 1;
        while ($j >= 0) {
            $token = $this->tokens[$j];
            if ((in_array($token->text, [')', ']'], true) || $this->isInterpolationClose($j)) && isset($this->match[$j])) {
                $j = $this->match[$j] - 1;
                continue;
            }
            if ($token->is([';', '{', '}', '(', '[', ',', '=', '?', ':', T_ECHO, T_PRINT, T_RETURN, T_CONCAT_EQUAL,
                T_COALESCE_EQUAL, T_DOUBLE_ARROW, T_OPEN_TAG, T_OPEN_TAG_WITH_ECHO, T_CLOSE_TAG, T_INLINE_HTML])) {
                break;
            }
            $j--;
        }
        $start = $j + 1;
        $boundary = $this->tokens[$j] ?? null;
        $isEcho = $boundary !== null && $boundary->is([T_ECHO, T_PRINT]);

        $count = count($this->tokens);
        for ($end = $anchor + 1; $end < $count; $end++) {
            $token = $this->tokens[$end];
            if ($this->isOpener($token) && isset($this->match[$end])) {
                $end = $this->match[$end];
                continue;
            }
            if ($token->is([';', ')', ']', '}', '?', ':', '=', T_DOUBLE_ARROW, T_CLOSE_TAG]) || (!$isEcho && $token->text === ',')) {
                break;
            }
        }

        // First argument of printf()/sprintf(): map the placeholders to the other arguments
        $formatArguments = null;
        if ($boundary?->text === '(' && in_array($this->callName($j - 1), ['printf', 'sprintf'], true) && isset($this->match[$j])) {
            $arguments = $this->splitTopLevel([$j + 1, $this->match[$j]], [',']);
            if ($arguments[0][1] === $end) {
                $formatArguments = array_slice($arguments, 1);
            }
        }

        return [[$start, $end], $isEcho ? ['.', ','] : ['.'], $formatArguments];
    }

    private function partsOf(array $range, array $separators): array {
        $parts = [];
        foreach ($this->splitTopLevel($range, $separators) as $operand) {
            if ($operand[0] >= $operand[1]) {
                continue;
            }
            $first = $this->tokens[$operand[0]];
            $last = $this->tokens[$operand[1] - 1];
            if ($operand[1] - $operand[0] === 1 && $first->is(T_CONSTANT_ENCAPSED_STRING)) {
                $parts[] = ['text', $this->unquote($first->text), $operand[0]];
            } elseif (($first->text === '"' && $last->text === '"') || ($first->is(T_START_HEREDOC) && $last->is(T_END_HEREDOC))) {
                array_push($parts, ...$this->interpolatedParts($operand));
            } else {
                $parts[] = ['expr', $operand, $operand[0]];
            }
        }
        return $parts;
    }

    private function interpolatedParts(array $range): array {
        $parts = [];
        $expressionStart = null;
        for ($i = $range[0] + 1; $i < $range[1] - 1; $i++) {
            if (!$this->tokens[$i]->is(T_ENCAPSED_AND_WHITESPACE)) {
                $expressionStart ??= $i;
                continue;
            }
            if ($expressionStart !== null) {
                $parts[] = ['expr', [$expressionStart, $i], $expressionStart];
                $expressionStart = null;
            }
            $parts[] = ['text', stripcslashes($this->tokens[$i]->text), $i];
        }
        if ($expressionStart !== null) {
            $parts[] = ['expr', [$expressionStart, $range[1] - 1], $expressionStart];
        }
        return $parts;
    }

    private function applyFormatArguments(array $parts, array $arguments): array {
        $result = [];
        $next = 0;
        foreach ($parts as $part) {
            if ($part[0] !== 'text') {
                $result[] = $part;
                continue;
            }
            $pieces = preg_split('/(%(?:\d+\$)?[-+ 0]*(?:\'.)?\d*(?:\.\d+)?[bcdeEfFgGosuxX%])/', $part[1], -1, PREG_SPLIT_DELIM_CAPTURE);
            foreach ($pieces as $k => $piece) {
                if ($k % 2 === 0 || $piece === '%%' || !str_ends_with($piece, 's')) {
                    // Literal text, an escaped percent sign, or a numeric placeholder (always safe)
                    $text = '0';
                    if ($k % 2 === 0) {
                        $text = $piece;
                    } elseif ($piece === '%%') {
                        $text = '%';
                    }
                    if ($text !== '') {
                        $result[] = ['text', $text, $part[2]];
                    }
                    continue;
                }
                $index = preg_match('/^%(\d+)\$/', $piece, $m) ? (int) $m[1] - 1 : $next++;
                $result[] = isset($arguments[$index]) ? ['expr', $arguments[$index], $arguments[$index][0]] : ['opaque', null, $part[2]];
            }
        }
        return $result;
    }

    private function isEscaped(array $range): bool {
        $range = $this->stripParens($range);
        if ($range[0] >= $range[1]) {
            return true;
        }
        $first = $this->tokens[$range[0]];
        if ($range[1] - $range[0] === 1 && $first->is([T_LNUMBER, T_DNUMBER, T_CONSTANT_ENCAPSED_STRING])) {
            return true;
        }
        if ($first->is([T_INT_CAST, T_DOUBLE_CAST, T_BOOL_CAST])) {
            return true;
        }
        $name = $this->callName($range[0]);
        return $name !== null && in_array($name, self::ESCAPING_FUNCTIONS, true) && ($this->match[$range[0] + 1] ?? null) === $range[1] - 1;
    }

    private function parseAttributes(string $source): array {
        $attributes = [];
        preg_match_all('/([^\s=\/>"\']+)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>"\']+))/', $source, $matches, PREG_SET_ORDER);
        foreach ($matches as $m) {
            $attributes[strtolower($m[1])] ??= ($m[2] ?? '') . ($m[3] ?? '') . ($m[4] ?? '');
        }
        return $attributes;
    }

    private function partAt(array $offsets, int $offset): array {
        $found = $offsets[0];
        foreach ($offsets as $entry) {
            if ($entry[0] > $offset) {
                break;
            }
            $found = $entry;
        }
        return $found;
    }

    // ---------------------------------------------------------------------
    // Token structure
    // ---------------------------------------------------------------------

    private function analyze(string $code): void {
        $this->code = $code;
        $this->tokens = [];
        $this->comments = [];
        foreach (\PhpToken::tokenize($code) as $token) {
            if ($token->is(T_WHITESPACE)) {
                continue;
            }
            if ($token->is([T_COMMENT, T_DOC_COMMENT])) {
                $this->comments[] = $token;
                continue;
            }
            $this->tokens[] = $token;
        }

        $this->match = [];
        $this->blockOf = [];
        $stack = [];
        $braces = [];
        foreach ($this->tokens as $i => $token) {
            if (in_array($token->text, [')', ']', '}'], true) && $stack !== []) {
                $open = array_pop($stack);
                $this->match[$open] = $i;
                $this->match[$i] = $open;
                if ($token->text === '}') {
                    array_pop($braces);
                }
            }
            $this->blockOf[$i] = $braces === [] ? -1 : end($braces);
            if ($this->isOpener($token)) {
                $stack[] = $i;
                if ($token->text !== '(' && $token->text !== '[') {
                    $braces[] = $i;
                }
            }
        }

        $this->functions = $this->collectFunctions();
        $this->regions = $this->collectConditionalRegions();
    }

    private function collectFunctions(): array {
        $functions = [];
        $count = count($this->tokens);
        foreach ($this->tokens as $k => $token) {
            if (!$token->is([T_FUNCTION, T_FN])) {
                continue;
            }
            $j = $k + 1;
            if (($this->tokens[$j] ?? null)?->text === '&') {
                $j++;
            }
            $name = null;
            if (($this->tokens[$j] ?? null)?->is(T_STRING)) {
                $name = strtolower($this->tokens[$j]->text);
                $j++;
            }
            if (($this->tokens[$j] ?? null)?->text !== '(' || !isset($this->match[$j])) {
                continue;
            }

            if ($token->is(T_FN)) {
                $arrow = $this->findForward($this->match[$j] + 1, $count, [T_DOUBLE_ARROW]);
                if ($arrow !== null) {
                    $functions[] = ['type' => 'arrow', 'name' => null, 'decl' => $k, 'start' => $arrow + 1, 'end' => $this->expressionEnd($arrow + 1, $count)];
                }
                continue;
            }

            $brace = $this->findForward($this->match[$j] + 1, $count, ['{', ';']);
            if ($brace !== null && $this->tokens[$brace]->text === '{' && isset($this->match[$brace])) {
                $functions[] = ['type' => $name === null ? 'closure' : 'named', 'name' => $name, 'decl' => $k, 'start' => $brace + 1, 'end' => $this->match[$brace]];
            }
        }
        return $functions;
    }

    private function collectConditionalRegions(): array {
        $regions = [];
        foreach ($this->tokens as $k => $token) {
            if ($token->is(T_IF) && !($this->tokens[$k - 1] ?? null)?->is(T_ELSE)) {
                array_push($regions, ...$this->ifChain($k)['branches']);
            }
        }
        return $regions;
    }

    /** @return array{branches: array, next: int} Branches of the if/elseif/else chain starting at $k */
    private function ifChain(int $k): array {
        $branches = [];
        $previous = [];
        $cursor = $k;
        $next = $k + 1;
        while (($this->tokens[$cursor + 1] ?? null)?->text === '(' && isset($this->match[$cursor + 1])) {
            $condition = [$cursor + 2, $this->match[$cursor + 1]];
            [$start, $end, $next] = $this->branchBody($this->match[$cursor + 1] + 1);
            $branches[] = [
                'first' => $cursor === $k, 'if' => $cursor, 'condition' => $condition, 'start' => $start, 'end' => $end,
                'guards' => array_merge([[$condition, false]], array_map(fn($c) => [$c, true], $previous)),
            ];
            $previous[] = $condition;

            $follower = $this->tokens[$next] ?? null;
            if ($follower?->is(T_ELSEIF)) {
                $cursor = $next;
                continue;
            }
            if ($follower?->is(T_ELSE)) {
                if (($this->tokens[$next + 1] ?? null)?->is(T_IF)) {
                    $cursor = $next + 1;
                    continue;
                }
                [$start, $end, $next] = $this->branchBody($next + 1);
                $branches[] = [
                    'first' => false, 'if' => $cursor, 'condition' => null, 'start' => $start, 'end' => $end,
                    'guards' => array_map(fn($c) => [$c, true], $previous),
                ];
            }
            if ($follower?->is(T_ENDIF)) {
                $next++;
            }
            break;
        }
        return ['branches' => $branches, 'next' => $next];
    }

    /** @return array{0: int, 1: int, 2: int} Body start, body end (exclusive) and the index following the body */
    private function branchBody(int $i): array {
        $count = count($this->tokens);
        $token = $this->tokens[$i] ?? null;
        if ($token === null) {
            return [$i, $i, $i];
        }
        if ($token->text === '{' && isset($this->match[$i])) {
            return [$i + 1, $this->match[$i], $this->match[$i] + 1];
        }
        if ($token->text === ':') {
            // Alternative syntax: if (...): ... elseif/else/endif
            $depth = 0;
            for ($j = $i + 1; $j < $count; $j++) {
                $t = $this->tokens[$j];
                if ($t->is(T_IF)) {
                    if ($this->isAlternativeIf($j)) {
                        $depth++;
                    } else {
                        $j = $this->ifChain($j)['next'] - 1;
                    }
                    continue;
                }
                if ($t->is(T_ENDIF)) {
                    if ($depth === 0) {
                        return [$i + 1, $j, $j];
                    }
                    $depth--;
                    continue;
                }
                if ($t->is([T_ELSEIF, T_ELSE]) && $depth === 0) {
                    return [$i + 1, $j, $j];
                }
                if ($this->isOpener($t) && isset($this->match[$j])) {
                    $j = $this->match[$j];
                }
            }
            return [$i + 1, $count, $count];
        }
        if ($token->is(T_IF)) {
            $next = $this->ifChain($i)['next'];
            return [$i, $next, $next];
        }
        if ($token->is([T_FOREACH, T_FOR, T_WHILE, T_SWITCH]) && ($this->tokens[$i + 1] ?? null)?->text === '(' && isset($this->match[$i + 1])) {
            [, $end, $next] = $this->branchBody($this->match[$i + 1] + 1);
            return [$i, $end, $next];
        }
        $end = $this->statementEnd($i);
        return [$i, $end + 1, $end + 1];
    }

    private function isAlternativeIf(int $j): bool {
        $open = $j + 1;
        return ($this->tokens[$open] ?? null)?->text === '(' && isset($this->match[$open])
            && ($this->tokens[$this->match[$open] + 1] ?? null)?->text === ':';
    }

    private function functionsContaining(int $p): array {
        $functions = array_values(array_filter($this->functions, fn($f) => $p >= $f['start'] && $p < $f['end']));
        usort($functions, fn($a, $b) => ($a['end'] - $a['start']) <=> ($b['end'] - $b['start']));
        return $functions;
    }

    private function innermostFunction(int $p): int {
        $functions = $this->functionsContaining($p);
        return $functions === [] ? -1 : $functions[0]['decl'];
    }

    private function blockEncloses(int $brace, int $p): bool {
        return $brace === -1 || ($p > $brace && $p < ($this->match[$brace] ?? PHP_INT_MAX));
    }

    private function exitsWithin(array $region, int $function): bool {
        for ($i = $region['start']; $i < $region['end']; $i++) {
            if ($this->tokens[$i]->is([T_RETURN, T_EXIT]) && $this->innermostFunction($i) === $function) {
                return true;
            }
        }
        return false;
    }

    private function findCalls(string $name): array {
        $calls = [];
        foreach ($this->tokens as $i => $token) {
            if ($this->callName($i) !== $name || !isset($this->match[$i + 1])) {
                continue;
            }
            $close = $this->match[$i + 1];
            $arguments = $close > $i + 2 ? $this->splitTopLevel([$i + 2, $close], [',']) : [];
            $calls[] = ['at' => $i, 'args' => array_values(array_filter($arguments, fn($a) => $a[0] < $a[1]))];
        }
        return $calls;
    }

    private function callName(int $i): ?string {
        $token = $this->tokens[$i] ?? null;
        if ($token === null || !$token->is([T_STRING, T_NAME_FULLY_QUALIFIED, T_NAME_QUALIFIED])
            || ($this->tokens[$i + 1] ?? null)?->text !== '('
            || ($this->tokens[$i - 1] ?? null)?->is([T_FUNCTION, T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON, T_NEW, T_CONST])) {
            return null;
        }
        return $this->normalizeName($token->text);
    }

    private function normalizeName(string $name): string {
        $name = strtolower($name);
        $name = substr($name, (int) strrpos('\\' . $name, '\\'));
        $method = strrpos($name, '::');
        return $method === false ? $name : substr($name, $method + 2);
    }

    private function stringValue(?array $range): ?string {
        if ($range === null || $range[1] - $range[0] !== 1 || !$this->tokens[$range[0]]->is(T_CONSTANT_ENCAPSED_STRING)) {
            return null;
        }
        return $this->unquote($this->tokens[$range[0]]->text);
    }

    private function unquote(string $text): string {
        $quote = $text[0] ?? '';
        $inner = substr($text, 1, -1);
        if ($quote === "'") {
            return str_replace(['\\\\', "\\'"], ['\\', "'"], $inner);
        }
        return $quote === '"' ? stripcslashes($inner) : $text;
    }

    private function sourceOf(array $range): string {
        if ($range[0] >= $range[1]) {
            return '';
        }
        $first = $this->tokens[$range[0]];
        $last = $this->tokens[$range[1] - 1];
        $source = substr($this->code, $first->pos, $last->pos + strlen($last->text) - $first->pos);
        return (string) preg_replace('/\s+/', ' ', trim($source));
    }

    private function isOpener(\PhpToken $token): bool {
        return in_array($token->text, ['(', '[', '{', '${'], true);
    }

    private function isInterpolationClose(int $j): bool {
        $open = $this->match[$j] ?? null;
        return $this->tokens[$j]->text === '}' && $open !== null && $this->tokens[$open]->is([T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES]);
    }

    private function stripParens(array $range): array {
        while ($range[0] < $range[1] && $this->tokens[$range[0]]->text === '(' && ($this->match[$range[0]] ?? null) === $range[1] - 1) {
            $range = [$range[0] + 1, $range[1] - 1];
        }
        return $range;
    }

    private function splitTopLevel(array $range, array $kinds): array {
        $pieces = [];
        $start = $range[0];
        for ($i = $range[0]; $i < $range[1]; $i++) {
            $token = $this->tokens[$i];
            if ($token->is($kinds)) {
                $pieces[] = [$start, $i];
                $start = $i + 1;
                continue;
            }
            if ($this->isOpener($token) && isset($this->match[$i]) && $this->match[$i] < $range[1]) {
                $i = $this->match[$i];
            }
        }
        $pieces[] = [$start, $range[1]];
        return $pieces;
    }

    private function findTopLevel(array $range, array $kinds): ?int {
        return $this->findForward($range[0], $range[1], $kinds);
    }

    private function findForward(int $from, int $to, array $kinds): ?int {
        for ($i = $from; $i < $to; $i++) {
            $token = $this->tokens[$i];
            if ($token->is($kinds)) {
                return $i;
            }
            if ($this->isOpener($token) && isset($this->match[$i])) {
                $i = $this->match[$i];
            }
        }
        return null;
    }

    private function expressionEnd(int $start, int $limit): int {
        for ($j = $start; $j < $limit; $j++) {
            $token = $this->tokens[$j];
            if ($this->isOpener($token) && isset($this->match[$j])) {
                $j = $this->match[$j];
                continue;
            }
            if ($token->is([';', ',', ')', ']', '}', T_CLOSE_TAG])) {
                return $j;
            }
        }
        return $limit;
    }

    private function statementEnd(int $i): int {
        $count = count($this->tokens);
        for ($j = $i; $j < $count; $j++) {
            $token = $this->tokens[$j];
            if ($token->is([';', T_CLOSE_TAG])) {
                return $j;
            }
            if ($this->isOpener($token) && isset($this->match[$j])) {
                $j = $this->match[$j];
            }
        }
        return $count - 1;
    }

    private function violation(string $rule, int $line, string $message): void {
        $this->found['violations'][] = "[$rule] line $line: $message";
    }

    private function warning(?int $line, string $message): void {
        $this->found['warnings'][] = ($line === null ? '' : "line $line: ") . $message;
    }
}

function main(array $argv): int {
    $targets = array_slice($argv, 1);
    if ($targets === []) {
        fwrite(STDERR, "Usage: php verify-seo-safety.php <file-or-directory> [more targets...]\n       php verify-seo-safety.php --self-test\n");
        return 2;
    }
    if ($targets[0] === '--self-test') {
        return SeoSafetyVerifier::runSelfTest(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'safety');
    }

    $verifier = new SeoSafetyVerifier();
    foreach ($targets as $target) {
        if (is_dir($target)) {
            $verifier->scanDirectory($target);
        } elseif (is_file($target)) {
            $verifier->scanFile($target);
        } else {
            fwrite(STDERR, "Target '$target' not found.\n");
            return 2;
        }
    }
    return $verifier->report();
}

// CLI entry point (skipped when the file is included as a library)
if (PHP_SAPI === 'cli' && realpath((string) ($_SERVER['SCRIPT_FILENAME'] ?? '')) === __FILE__) {
    exit(main($_SERVER['argv']));
}
