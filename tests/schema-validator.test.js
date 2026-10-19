#!/usr/bin/env node
/**
 * Regression tests for the schema-graph-validator.js CLI exit codes.
 * Usage: node tests/schema-validator.test.js
 */

const { spawnSync } = require('child_process');
const fs = require('fs');
const os = require('os');
const path = require('path');

const root = path.join(__dirname, '..');
const validator = path.join(root, 'tools', 'schema-graph-validator.js');
let failures = 0;

function expectExit(label, args, expected) {
  const run = spawnSync(process.execPath, [validator, ...args], { cwd: root, encoding: 'utf8' });
  if (run.status === expected) {
    console.log(`  \x1b[32m✔\x1b[0m ${label}`);
    return;
  }
  failures++;
  console.error(`  \x1b[31m✘\x1b[0m ${label}: expected exit ${expected}, got ${run.status}\n${run.stdout}${run.stderr}`);
}

console.log('\x1b[36mRunning schema-graph-validator CLI regression tests...\x1b[0m');
expectExit('No target is a usage error, not a pass', [], 2);
expectExit('Missing file fails', ['does-not-exist.json'], 1);
expectExit('Broken project-02 starter fails', ['projects/project-02-schema-knowledge-graph/starter/broken-schema.json'], 1);
expectExit('Connected project-02 solution passes', ['projects/project-02-schema-knowledge-graph/solution/connected-knowledge-graph.json'], 0);

// HTML pages: every JSON-LD block is validated, whatever its attributes (Yoast adds a class)
const page = path.join(fs.mkdtempSync(path.join(os.tmpdir(), 'schema-')), 'page.html');
fs.writeFileSync(page, '<script type="application/ld+json" class="yoast-schema-graph">{"@type":"WebSite","name":"X","url":"https://example.com"}</script>\n'
  + '<script type="application/ld+json">{"@type":"Product","name":"P","offers":{"@type":"Offer"}}</script>');
expectExit('A broken second JSON-LD block fails the page', [page], 1);
fs.writeFileSync(page, '<script type="application/ld+json" class="yoast-schema-graph">{"@type":"WebSite","name":"X","url":"https://example.com"}</script>');
expectExit('A Yoast-style block with extra attributes is read', [page], 0);
fs.rmSync(path.dirname(page), { recursive: true, force: true });

if (failures > 0) {
  console.error(`\x1b[31m${failures} schema-graph-validator regression test(s) FAILED.\x1b[0m`);
  process.exit(1);
}
console.log('\x1b[32mAll schema-graph-validator CLI regression tests passed!\x1b[0m\n');
