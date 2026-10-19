#!/usr/bin/env node
/**
 * Genisoft WordPress SEO Harness - Schema.org JSON-LD Graph Validator
 *
 * Validates JSON-LD structured data against Google Search Central requirements
 * and the harness graph rules: every @graph entity has an @id, and every @id
 * reference resolves to an entity defined on the same page.
 * Nested entities, @type arrays and multiple JSON-LD blocks per page are supported.
 *
 * @author Abdelmonem Lefi (Genisoft)
 * @license MIT
 */

const fs = require('fs');

// Properties Google Search Central marks as required for rich results
const GOOGLE_REQUIRED_FIELDS = {
  Product: ['name'],
  LocalBusiness: ['name', 'address'],
  BreadcrumbList: ['itemListElement']
};

// Properties Google recommends (Article has no required property)
const GOOGLE_RECOMMENDED_FIELDS = {
  Article: ['headline', 'image', 'datePublished', 'author'],
  NewsArticle: ['headline', 'image', 'datePublished', 'author'],
  BlogPosting: ['headline', 'image', 'datePublished', 'author']
};

// Harness conventions (AGENTS.md / SEO_RULES_SPEC.md), not Google requirements
const HARNESS_REQUIRED_FIELDS = {
  WebSite: ['name', 'url'],
  Organization: ['name', 'url']
};

const isObject = (value) => value !== null && typeof value === 'object' && !Array.isArray(value);
const isPresent = (value) => value !== undefined && value !== null && value !== '';
const asArray = (value) => (Array.isArray(value) ? value : [value]);
const isReference = (value) => isObject(value) && isPresent(value['@id']) && Object.keys(value).length === 1;
const typesOf = (entity) => asArray(entity['@type']).filter((type) => typeof type === 'string');

/** Returns the content of every <script type="application/ld+json"> block, whatever its other attributes */
function extractJsonLdBlocks(html) {
  const pattern = /<script\b[^>]*\btype\s*=\s*["']?application\/ld\+json["']?[^>]*>([\s\S]*?)<\/script>/gi;
  return [...html.matchAll(pattern)].map((match) => match[1]);
}

class SchemaValidator {
  constructor() {
    this.errors = [];
    this.warnings = [];
    this.entityMap = new Map();
  }

  extractEntities(data) {
    if (Array.isArray(data)) {
      return data;
    }
    if (data && Array.isArray(data['@graph'])) {
      return data['@graph'];
    }
    return [data];
  }

  validate(jsonLd) {
    return this.validateDocuments([jsonLd]);
  }

  /** Validates every JSON-LD block of one page together, so @id references can cross blocks */
  validateDocuments(documents) {
    this.errors = [];
    this.warnings = [];
    this.entityMap.clear();

    const roots = [];
    documents.forEach((document, index) => {
      let data = document;
      if (typeof document === 'string') {
        try {
          data = JSON.parse(document);
        } catch (e) {
          const block = documents.length > 1 ? ` in JSON-LD block #${index + 1}` : '';
          this.errors.push(`Invalid JSON syntax${block}: ${e.message}`);
          return;
        }
      }
      const isGraph = isObject(data) && Array.isArray(data['@graph']);
      for (const entity of this.extractEntities(data)) {
        roots.push({ entity, isGraph });
      }
    });

    // Index every entity definition, top-level or nested, by @id
    roots.forEach(({ entity }) => this.indexEntities(entity));

    for (const { entity, isGraph } of roots) {
      if (!isObject(entity) || !isPresent(entity['@type'])) {
        this.errors.push(`Entity is missing required '@type' property: ${JSON.stringify(entity).slice(0, 80)}...`);
        continue;
      }
      if (isGraph && !isPresent(entity['@id'])) {
        this.errors.push(`[${typesOf(entity).join(', ')}] Missing '@id' required by the harness for every @graph entity`);
      }
      this.walk(entity);
    }

    return this.result();
  }

  indexEntities(node) {
    if (Array.isArray(node)) {
      node.forEach((child) => this.indexEntities(child));
      return;
    }
    if (!isObject(node) || isReference(node)) {
      return;
    }
    if (isPresent(node['@id'])) {
      if (this.entityMap.has(node['@id'])) {
        this.warnings.push(`Duplicate @id detected: "${node['@id']}"`);
      }
      this.entityMap.set(node['@id'], node);
    }
    Object.values(node).forEach((value) => this.indexEntities(value));
  }

  walk(node) {
    if (Array.isArray(node)) {
      node.forEach((child) => this.walk(child));
      return;
    }
    if (!isObject(node)) {
      return;
    }
    if (isReference(node)) {
      this.checkReference(node['@id']);
      return;
    }
    if (isPresent(node['@type'])) {
      this.validateEntity(node);
    }
    for (const [key, value] of Object.entries(node)) {
      if (key !== '@context' && (isObject(value) || Array.isArray(value))) {
        this.walk(value);
      }
    }
  }

  checkReference(id) {
    if (!this.entityMap.has(id)) {
      this.errors.push(`Dangling @id reference: '${id}' is referenced, but no entity with that @id is defined on this page.`);
    }
  }

  resolve(value) {
    return isReference(value) ? this.entityMap.get(value['@id']) : value;
  }

  validateEntity(entity) {
    const types = typesOf(entity);
    if (types.length === 0) {
      this.errors.push(`Invalid '@type' value: ${JSON.stringify(entity['@type'])}`);
      return;
    }

    for (const type of types) {
      this.checkFields(entity, type, GOOGLE_REQUIRED_FIELDS[type], 'required by Google', this.errors);
      this.checkFields(entity, type, HARNESS_REQUIRED_FIELDS[type], 'required by the harness', this.errors);
      this.checkFields(entity, type, GOOGLE_RECOMMENDED_FIELDS[type], 'recommended by Google', this.warnings);

      if (type === 'Product') {
        this.validateProduct(entity);
      }
      if (type === 'BreadcrumbList') {
        this.validateBreadcrumbItems(entity);
      }
      if (type === 'FAQPage') {
        this.warnings.push('[FAQPage] FAQ rich results are no longer shown in Google Search (since May 7, 2026): this markup earns no rich result.');
      }
    }
  }

  checkFields(entity, type, fields, requirement, target) {
    for (const field of fields || []) {
      if (!isPresent(entity[field])) {
        target.push(`[${type}] Missing property ${requirement}: '${field}'`);
      }
    }
  }

  validateProduct(entity) {
    if (!['offers', 'review', 'aggregateRating'].some((field) => isPresent(entity[field]))) {
      this.errors.push(`[Product] Missing property required by Google: one of 'offers', 'review' or 'aggregateRating'`);
    }
    if (!isPresent(entity.offers)) {
      return;
    }

    asArray(entity.offers).forEach((raw) => this.validateOffer(raw));
  }

  validateOffer(raw) {
    if (isReference(raw) && !this.entityMap.has(raw['@id'])) {
      return; // Reported as a dangling reference
    }
    const offer = this.resolve(raw);
    if (!isObject(offer)) {
      this.errors.push(`[Product -> Offer] Invalid offer: ${JSON.stringify(raw)}`);
      return;
    }
    const specification = isObject(offer.priceSpecification) ? offer.priceSpecification : {};
    if (typesOf(offer).includes('AggregateOffer')) {
      if (!isPresent(offer.lowPrice)) {
        this.errors.push(`[Product -> AggregateOffer] Missing 'lowPrice' property required by Google`);
      }
    } else if (!isPresent(offer.price) && !isPresent(specification.price)) {
      this.errors.push(`[Product -> Offer] Missing 'price' property required by Google (or priceSpecification.price)`);
    }
    if (!isPresent(offer.priceCurrency) && !isPresent(specification.priceCurrency)) {
      this.warnings.push(`[Product -> Offer] Missing 'priceCurrency' (e.g. USD, EUR): required for Google merchant listings`);
    }
  }

  validateBreadcrumbItems(entity) {
    if (!isPresent(entity.itemListElement)) {
      return;
    }
    const items = asArray(entity.itemListElement);
    if (items.length < 2) {
      this.warnings.push('[BreadcrumbList] Google recommends at least two ListItem entries');
    }
    items.forEach((raw, idx) => {
      const label = `[BreadcrumbList item #${idx + 1}]`;
      const item = this.resolve(raw);
      if (!isObject(item)) {
        this.errors.push(`${label} Invalid ListItem: ${JSON.stringify(raw)}`);
        return;
      }
      if (!isPresent(item.position)) {
        this.errors.push(`${label} Missing 'position' required by Google`);
      }
      // Google: only the last breadcrumb may omit 'item'; 'name' may come from an item Thing
      if (!isPresent(item.item) && idx < items.length - 1) {
        this.errors.push(`${label} Missing 'item' required by Google (only the last item may omit it)`);
      }
      const target = this.resolve(item.item);
      if (!isPresent(item.name) && !(isObject(target) && isPresent(target.name))) {
        this.errors.push(`${label} Missing 'name' required by Google`);
      }
    });
  }

  result() {
    return {
      valid: this.errors.length === 0,
      errors: this.errors,
      warnings: this.warnings,
      indexedEntities: this.entityMap.size
    };
  }
}

// Self-test suite
function runSelfTest() {
  console.log('\x1b[36mRunning Schema Graph Validator self-tests...\x1b[0m');
  const validator = new SchemaValidator();
  let failures = 0;
  const expect = (label, condition, result) => {
    if (condition) {
      console.log(`  \x1b[32m✔\x1b[0m ${label}`);
      return;
    }
    failures++;
    console.error(`  \x1b[31m✘ ${label}\x1b[0m`, JSON.stringify(result));
  };
  const graph = (...entities) => ({ '@context': 'https://schema.org', '@graph': entities });
  const organization = { '@type': 'Organization', '@id': 'https://example.com/#organization', name: 'Genisoft', url: 'https://example.com' };

  let res = validator.validate(graph(organization, {
    '@type': 'WebSite', '@id': 'https://example.com/#website', url: 'https://example.com', name: 'Genisoft Agency',
    publisher: { '@id': 'https://example.com/#organization' }
  }));
  expect('Valid interconnected graph passed.', res.valid && res.warnings.length === 0, res);

  res = validator.validate({ '@context': 'https://schema.org', '@type': 'Product', name: 'Custom SEO Audit', offers: { '@type': 'Offer', priceCurrency: 'EUR' } });
  expect('Missing price in Product offer correctly caught.', !res.valid && res.errors.some((e) => e.includes("'price'")), res);

  res = validator.validate(graph({
    '@type': 'Article', '@id': 'https://example.com/post/#article', headline: 'WordPress Harness Engineering',
    image: 'https://example.com/cover.jpg', datePublished: '2026-10-19', author: { '@id': '#missing-author' }
  }));
  expect('Dangling @id entity reference caught.', !res.valid && res.errors.some((e) => e.includes('Dangling @id reference')), res);

  res = validator.validate(graph({ '@type': 'WebSite', '@id': 'https://example.com/#website', name: 'X', url: 'https://example.com', publisher: { '@id': 'https://example.com/#organization' } }));
  expect('Dangling absolute @id reference caught.', !res.valid && res.errors.some((e) => e.includes('https://example.com/#organization')), res);

  res = validator.validate(graph({ '@type': ['Organization', 'LocalBusiness'], '@id': 'https://example.com/#shop' }));
  expect('@type arrays are validated for every type.', res.errors.some((e) => e.includes('[Organization]')) && res.errors.some((e) => e.includes('[LocalBusiness]')), res);

  res = validator.validate(graph({ '@type': 'WebPage', '@id': 'https://example.com/p/#webpage', mainEntity: { '@type': 'Product', name: 'P', offers: { '@type': 'Offer' } } }));
  expect('Nested Product is validated.', !res.valid && res.errors.some((e) => e.includes("'price'")), res);

  res = validator.validate(graph({ '@type': 'Organization', name: 'X', url: 'https://example.com' }));
  expect('@graph entity without @id caught.', !res.valid && res.errors.some((e) => e.includes("'@id'")), res);

  res = validator.validate(graph(
    { '@type': 'Product', '@id': 'https://example.com/p/#product', name: 'P', offers: [null] },
    { '@type': 'BreadcrumbList', '@id': 'https://example.com/p/#breadcrumb', itemListElement: [null] }
  ));
  expect('Null offers and list items are reported, not crashed on.', !res.valid && res.errors.some((e) => e.includes('Invalid offer')) && res.errors.some((e) => e.includes('Invalid ListItem')), res);

  res = validator.validate(graph(
    { '@type': 'Product', '@id': 'https://example.com/p/#product', name: 'P', aggregateRating: { '@type': 'AggregateRating', ratingValue: 4.8, reviewCount: 12 } },
    { '@type': 'Article', '@id': 'https://example.com/post/#article', headline: 'H', datePublished: '2026-10-19', author: { '@type': 'Person', name: 'A' } },
    { '@type': 'BreadcrumbList', '@id': 'https://example.com/p/#breadcrumb', itemListElement: [
      { '@type': 'ListItem', position: 1, name: 'Home', item: 'https://example.com/' },
      { '@type': 'ListItem', position: 2, name: 'P' }
    ] }
  ));
  expect('Google-valid markup passes (aggregateRating-only Product, Article without image, last breadcrumb without item).', res.valid && res.warnings.some((w) => w.includes("'image'")), res);

  const blocks = extractJsonLdBlocks(
    '<script type="application/ld+json" class="yoast-schema-graph">{"@type":"WebSite","name":"X","url":"https://example.com"}</script>' +
    '<script type=\'application/ld+json\'>{"@type":"Product","name":"P","offers":{"@type":"Offer"}}</script>'
  );
  res = validator.validateDocuments(blocks);
  expect('Every JSON-LD block of a page is extracted and validated.', blocks.length === 2 && !res.valid, res);

  if (failures > 0) {
    console.error(`\x1b[31m${failures} Schema Graph Validator self-test(s) FAILED.\x1b[0m\n`);
    process.exit(1);
  }
  console.log('\x1b[32mAll Schema Graph Validator self-tests passed successfully!\x1b[0m\n');
}

// CLI Execution
if (require.main === module) {
  const args = process.argv.slice(2);
  if (args.includes('--test')) {
    runSelfTest();
    process.exit(0);
  }

  const target = args[0];
  if (!target) {
    // A gate run without a file must never count as a pass
    console.error('Usage: node schema-graph-validator.js <file.json|file.html> [--test]');
    process.exit(2);
  }

  try {
    let documents = [fs.readFileSync(target, 'utf8')];
    if (target.endsWith('.html') || target.endsWith('.php')) {
      documents = extractJsonLdBlocks(documents[0]);
      if (documents.length === 0) {
        console.error('\x1b[31mError: No <script type="application/ld+json"> tag found in HTML file.\x1b[0m');
        process.exit(1);
      }
    }

    const validator = new SchemaValidator();
    const result = validator.validateDocuments(documents);

    console.log(`JSON-LD blocks: ${documents.length} | Indexed Entities: ${result.indexedEntities}`);
    if (result.warnings.length > 0) {
      console.log('\x1b[33mWarnings:\x1b[0m');
      result.warnings.forEach(w => console.log(`  - ${w}`));
    }

    if (!result.valid) {
      console.log('\x1b[31mErrors:\x1b[0m');
      result.errors.forEach(e => console.log(`  - ${e}`));
      process.exit(1);
    } else {
      console.log('\x1b[32m✔ Schema JSON-LD validation PASSED!\x1b[0m');
      process.exit(0);
    }
  } catch (err) {
    console.error(`Error reading file: ${err.message}`);
    process.exit(1);
  }
}

module.exports = { SchemaValidator, extractJsonLdBlocks };
