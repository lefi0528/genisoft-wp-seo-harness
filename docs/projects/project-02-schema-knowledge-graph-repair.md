# Project 02: Schema Knowledge Graph Repair

## Objective
Convert fragmented microdata into a unified, interconnected Schema.org `@graph` that passes Google Rich Results testing with zero dangling references.

---

## Step 1: Inspect Broken Microdata
Open `projects/project-02-schema-knowledge-graph/starter/broken-schema.json`.

Notice the issues:
- Product offer is missing `priceCurrency`.
- Article references an `@id` node `#unknown-author` that does not exist in the graph.

Run the validator:
```bash
node tools/schema-graph-validator.js projects/project-02-schema-knowledge-graph/starter/broken-schema.json
```

Output:
```text
Errors:
  - [Product -> Offer] Missing 'priceCurrency' property (e.g. USD, EUR)
Warnings:
  - Dangling @id reference: entity references '#unknown-author', but no entity with that @id exists in the graph.
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
Indexed Entities: 4
✔ Schema JSON-LD validation PASSED!
```
