# Project 02: Schema Knowledge Graph Repair

## Objective
Repair a fragmented JSON-LD graph into a connected Schema.org `@graph`: Google's required properties present, every entity identified by an `@id`, and zero dangling references.

---

## Step 1: Inspect the Broken Graph
Open `projects/project-02-schema-knowledge-graph/starter/broken-schema.json`.

Notice the issues:
- The Product and the Article have no `@id`, so nothing can reference them.
- The Product offer is missing `priceCurrency` (required for Google merchant listings).
- The Article references an `@id` node `#unknown-author` that does not exist in the graph.

Run the validator:
```bash
node tools/schema-graph-validator.js projects/project-02-schema-knowledge-graph/starter/broken-schema.json
```

Output:
```text
JSON-LD blocks: 1 | Indexed Entities: 0
Warnings:
  - [Product -> Offer] Missing 'priceCurrency' (e.g. USD, EUR): required for Google merchant listings
Errors:
  - [Product] Missing '@id' required by the harness for every @graph entity
  - [Article] Missing '@id' required by the harness for every @graph entity
  - Dangling @id reference: '#unknown-author' is referenced, but no entity with that @id is defined on this page.
```

---

## Step 2: Verify the Repaired Knowledge Graph
Open `projects/project-02-schema-knowledge-graph/solution/connected-knowledge-graph.json`.

Run the validator:
```bash
node tools/schema-graph-validator.js projects/project-02-schema-knowledge-graph/solution/connected-knowledge-graph.json
```

Output:
```text
JSON-LD blocks: 1 | Indexed Entities: 4
✔ Schema JSON-LD validation PASSED!
```
