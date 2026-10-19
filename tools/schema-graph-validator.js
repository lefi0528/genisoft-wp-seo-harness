#!/usr/bin/env node
/**
 * Genisoft WordPress SEO Harness - Schema.org JSON-LD Graph Validator
 * 
 * Validates JSON-LD structured data against Google Search Central specifications.
 * Supports interconnected entity graphs (@graph), @id cross-referencing,
 * and standard Google Rich Result entity schemas.
 * 
 * @author Abdelmonem Lefi (Genisoft)
 * @license MIT
 */

const fs = require('fs');

const REQUIRED_FIELDS_BY_TYPE = {
  WebSite: ['name', 'url'],
  Organization: ['name', 'url'],
  LocalBusiness: ['name', 'address'],
  Article: ['headline', 'image', 'datePublished', 'author'],
  BlogPosting: ['headline', 'image', 'datePublished', 'author'],
  Product: ['name', 'offers'],
  BreadcrumbList: ['itemListElement'],
  FAQPage: ['mainEntity']
};

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
    this.errors = [];
    this.warnings = [];
    this.entityMap.clear();

    let data;
    if (typeof jsonLd === 'string') {
      try {
        data = JSON.parse(jsonLd);
      } catch (e) {
        this.errors.push(`Invalid JSON syntax: ${e.message}`);
        return this.result();
      }
    } else {
      data = jsonLd;
    }

    const entities = this.extractEntities(data);

    // Index all entities by @id
    for (const entity of entities) {
      if (entity && entity['@id']) {
        if (this.entityMap.has(entity['@id'])) {
          this.warnings.push(`Duplicate @id detected: "${entity['@id']}"`);
        }
        this.entityMap.set(entity['@id'], entity);
      }
    }

    // Validate each entity
    for (const entity of entities) {
      this.validateEntity(entity);
    }

    return this.result();
  }

  validateRequiredFields(entity, type) {
    const required = REQUIRED_FIELDS_BY_TYPE[type];
    if (!required) return;

    for (const field of required) {
      if (entity[field] === undefined || entity[field] === null || entity[field] === '') {
        this.errors.push(`[${type}] Missing required Google Rich Result property: '${field}'`);
      }
    }
  }

  validateProductOffers(entity) {
    if (!entity.offers) return;
    const offers = Array.isArray(entity.offers) ? entity.offers : [entity.offers];
    for (const offer of offers) {
      if (offer.price === undefined || offer.price === null || offer.price === '') {
        this.errors.push(`[Product -> Offer] Missing 'price' property`);
      }
      if (!offer.priceCurrency) {
        this.errors.push(`[Product -> Offer] Missing 'priceCurrency' property (e.g. USD, EUR)`);
      }
    }
  }

  validateBreadcrumbItems(entity) {
    if (!Array.isArray(entity.itemListElement)) return;
    entity.itemListElement.forEach((item, idx) => {
      if (item.position === undefined) {
        this.errors.push(`[BreadcrumbList item #${idx + 1}] Missing 'position'`);
      }
      if (!item.name) {
        this.warnings.push(`[BreadcrumbList item #${idx + 1}] Missing 'name'`);
      }
    });
  }

  validateEntity(entity) {
    if (!entity || !entity['@type']) {
      this.errors.push(`Entity is missing required '@type' property: ${JSON.stringify(entity).slice(0, 80)}...`);
      return;
    }

    const type = entity['@type'];
    this.validateRequiredFields(entity, type);

    if (type === 'Product') {
      this.validateProductOffers(entity);
    }

    if (type === 'BreadcrumbList') {
      this.validateBreadcrumbItems(entity);
    }

    this.checkReferences(entity);
  }

  validateReferenceNode(val) {
    if (val && typeof val === 'object' && val['@id'] && Object.keys(val).length === 1) {
      const refId = val['@id'];
      if (!this.entityMap.has(refId) && !refId.startsWith('http://') && !refId.startsWith('https://')) {
        this.warnings.push(`Dangling @id reference: entity references '${refId}', but no entity with that @id exists in the graph.`);
      }
      return true;
    }
    return false;
  }

  checkReferences(obj) {
    if (!obj || typeof obj !== 'object') return;

    for (const [key, val] of Object.entries(obj)) {
      if (key === '@id') continue;
      if (!this.validateReferenceNode(val) && val && typeof val === 'object') {
        this.checkReferences(val);
      }
    }
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

  const validGraph = {
    "@context": "https://schema.org",
    "@graph": [
      {
        "@type": "Organization",
        "@id": "https://example.com/#organization",
        "name": "Genisoft",
        "url": "https://example.com"
      },
      {
        "@type": "WebSite",
        "@id": "https://example.com/#website",
        "url": "https://example.com",
        "name": "Genisoft Agency",
        "publisher": { "@id": "https://example.com/#organization" }
      }
    ]
  };

  const res1 = validator.validate(validGraph);
  if (!res1.valid || res1.warnings.length > 0) {
    console.error('\x1b[31mFAIL: Valid graph flagged with errors/warnings\x1b[0m', res1);
    process.exit(1);
  }
  console.log('  \x1b[32m✔\x1b[0m Valid interconnected graph passed.');

  const invalidProduct = {
    "@context": "https://schema.org",
    "@type": "Product",
    "name": "Custom SEO Audit",
    "offers": { "@type": "Offer", "priceCurrency": "EUR" }
  };

  const res2 = validator.validate(invalidProduct);
  if (res2.valid || !res2.errors.some(e => e.includes("'price'"))) {
    console.error('\x1b[31mFAIL: Invalid product was not flagged for missing price\x1b[0m', res2);
    process.exit(1);
  }
  console.log('  \x1b[32m✔\x1b[0m Missing price in Product offer correctly caught.');

  const danglingRef = {
    "@context": "https://schema.org",
    "@graph": [
      {
        "@type": "Article",
        "headline": "WordPress Harness Engineering",
        "image": "https://example.com/cover.jpg",
        "datePublished": "2026-10-19",
        "author": { "@id": "#missing-author" }
      }
    ]
  };

  const res3 = validator.validate(danglingRef);
  if (!res3.warnings.some(w => w.includes('Dangling @id reference'))) {
    console.error('\x1b[31mFAIL: Dangling @id was not detected\x1b[0m', res3);
    process.exit(1);
  }
  console.log('  \x1b[32m✔\x1b[0m Dangling @id entity reference caught.');

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
    let rawContent = fs.readFileSync(target, 'utf8');
    if (target.endsWith('.html') || target.endsWith('.php')) {
      const match = rawContent.match(/<script\s+type=["']application\/ld\+json["']>([\s\S]*?)<\/script>/i);
      if (match) {
        rawContent = match[1];
      } else {
        console.error('\x1b[31mError: No <script type="application/ld+json"> tag found in HTML file.\x1b[0m');
        process.exit(1);
      }
    }

    const validator = new SchemaValidator();
    const result = validator.validate(rawContent);

    console.log(`Indexed Entities: ${result.indexedEntities}`);
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

module.exports = { SchemaValidator };
