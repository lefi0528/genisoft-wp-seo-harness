<?php
declare(strict_types=1);

/**
 * Regression tests for the SEO safety scanner on the repository's own files and task gates.
 * Usage: php tests/run-php-tests.php
 */

require __DIR__ . '/../tools/verify-seo-safety.php';

use Genisoft\WpSeoHarness\SeoSafetyVerifier;

$root = dirname(__DIR__);
$scanner = $root . '/tools/verify-seo-safety.php';
$failures = 0;

$check = function (bool $ok, string $label, string $details = '') use (&$failures): void {
    echo $ok ? "  \033[32m✔\033[0m $label\n" : "  \033[31m✘\033[0m $label" . ($details !== '' ? " ($details)" : '') . "\n";
    $failures += $ok ? 0 : 1;
};
$rules = function (string $file): array {
    $result = (new SeoSafetyVerifier())->checkContent((string) file_get_contents($file), $file);
    return SeoSafetyVerifier::rulesOf($result['violations']);
};
$run = function (string $arguments) use ($root): int {
    $command = escapeshellarg(PHP_BINARY) . ' ' . $arguments;
    $cwd = getcwd();
    chdir($root);
    exec($command . ' 2>&1', $output, $code);
    chdir($cwd);
    return $code;
};

echo "\033[36mRunning SEO safety regression tests...\033[0m\n";

// Including the scanner must not run its CLI (it used to scan itself and exit)
$check(class_exists(SeoSafetyVerifier::class), 'verify-seo-safety.php can be included as a library');

// project-01: the starter has three bugs, the solution none
$starter = $rules("$root/projects/project-01-baseline-vs-harnessed/starter/vulnerable-seo-functions.php");
$expected = ['SEO-CANONICAL-HTTP', 'SEO-META-UNESCAPED', 'SEO-NOINDEX'];
$check($starter === $expected, 'project-01 starter: all 3 bugs are caught', implode(', ', $starter));
$solution = $rules("$root/projects/project-01-baseline-vs-harnessed/solution/guarded-seo-functions.php");
$check($solution === [], 'project-01 solution passes', implode(', ', $solution));

// The harness's own code must pass its own scanner
foreach (['tools/wp-seo-guard.php', 'tools/verify-seo-safety.php', 'examples/genisoft-live-seo-enforcer.php'] as $file) {
    $found = $rules("$root/$file");
    $check($found === [], "$file has no false positive", implode(', ', $found));
}

// CLI exit codes
$scannerArg = escapeshellarg($scanner);
$check($run($scannerArg) === 2, 'Scanner without a target is a usage error, not a pass');
$check($run("$scannerArg tools") === 0, 'Scanning tools/ passes');
$check($run("$scannerArg projects/project-01-baseline-vs-harnessed/starter") === 1, 'Scanning the vulnerable starter fails');

// Task gates: executable ones must pass, all of them must name their target
$tasks = json_decode((string) file_get_contents("$root/templates/seo_tasks.json"), true)['tasks'];
foreach ($tasks as $task) {
    $gate = $task['verification_gate'];
    if (preg_match('/(verify-seo-safety\.php|schema-graph-validator\.js|php -l)/', $gate)) {
        $check(preg_match('/(verify-seo-safety\.php|schema-graph-validator\.js|php -l)\s+\S+/', $gate) === 1, "{$task['id']} gate names the files it checks", $gate);
    }
    if ($task['id'] === 'SEO-01') {
        $code = $run(preg_replace('/^php /', '', $gate));
        $check($code === 0, 'SEO-01 gate passes on the shipped runtime guard', "exit $code");
    }
}

if ($failures > 0) {
    echo "\033[31m$failures SEO safety regression test(s) FAILED.\033[0m\n";
    exit(1);
}
echo "\033[32mAll SEO safety regression tests passed!\033[0m\n";
