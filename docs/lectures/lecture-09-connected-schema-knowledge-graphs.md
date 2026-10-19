# Lecture 09: Connected Schema Knowledge Graphs

## Microdata vs. Connected JSON-LD `@graph`

Most WordPress sites have fragmented structured data:
- SEO plugins output an Organization.
- E-commerce plugins output isolated Products.
- Page builders inject partial Microdata like `<div itemtype="https://schema.org/Rating">`.

Search engines struggle to connect these disjointed entities into a unified Knowledge Graph.

---

## The Unified `@graph` Standard

Google Search Central explicitly favors interconnected entities within a single JSON-LD block:

```json
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "Organization",
      "@id": "https://example.com/#organization",
      "name": "Acme Agency",
      "url": "https://example.com/"
    },
    {
      "@type": "WebSite",
      "@id": "https://example.com/#website",
      "url": "https://example.com/",
      "publisher": { "@id": "https://example.com/#organization" }
    },
    {
      "@type": "WebPage",
      "@id": "https://example.com/services/#webpage",
      "url": "https://example.com/services/",
      "isPartOf": { "@id": "https://example.com/#website" }
    }
  ]
}
```

The Harness tool `schema-graph-validator.js` enforces this graph topology and prevents dangling `@id` references.
