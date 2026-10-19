# Genisoft WordPress SEO Harness 🛡️⚡
> **Autonomous Technical SEO & Core Web Vitals Engineering Harness for AI Coding Agents**  
> A complete, project-based curriculum and runtime framework for Claude Code, OpenAI Codex, Antigravity, and Cursor.  
> Conceived & Engineered by **Abdelmonem Lefi (Genisoft)**.

[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-21759B?style=for-the-badge&logo=wordpress&logoColor=white)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-8.1%20--%208.3-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![AI Agents Ready](https://img.shields.io/badge/AI%20Agents-Claude%20Code%20%7C%20Codex%20%7C%20Antigravity-8A2BE2?style=for-the-badge)](https://github.com/walkinglabs/learn-harness-engineering)
[![License: MIT](https://img.shields.io/badge/License-MIT-success?style=for-the-badge)](LICENSE)
[![Schema.org Validated](https://img.shields.io/badge/Schema.org-%40graph%20Validated-orange?style=for-the-badge)](https://schema.org)
[![Fiverr Pro Vetted](https://img.shields.io/badge/Fiverr%20Pro-Vetted%20Engineering-00B22D?style=for-the-badge)](https://github.com/lefi0528)

---

## 💡 The Core Problem

Large Language Models (LLMs) are brilliant at writing individual functions, but **unreliable when left unconstrained on live WordPress codebases**:
- 🚨 **Accidental De-Indexing**: Agents frequently write inverted `wp_robots` filters that inject `noindex` on homepages or key landing pages.
- 🔗 **Broken Canonical Loops**: Hallucinated canonicals with relative paths, HTTP schemes, or circular redirects.
- 🧩 **Fragmented Schema.org**: Disjointed JSON-LD snippets with broken or dangling `@id` entity references that fail Google Rich Results testing.
- 💥 **Production Crashes (WSOD)**: Single syntax errors or hook collisions in `functions.php` triggering Error 500.

**The model is smart. The Genisoft Harness makes it 100% reliable.**

---

## 📚 Complete Curriculum & Architecture

This repository is both a **production runtime framework** and a **complete 10-lecture / 4-project curriculum** inspired by the frontier research of Anthropic, OpenAI, and Martin Fowler.

```text
┌─────────────────────────────────────────────────────────────────────────────┐
│                      GENISOFT HARNESS GOVERNANCE ENGINE                     │
│                                                                             │
│  ┌───────────────────┐    ┌────────────────────┐    ┌────────────────────┐  │
│  │ 1. INSTRUCTIONS   │    │ 2. STATE PERSIST   │    │ 3. VERIFICATION    │  │
│  │    AGENTS.md      │    │    seo_tasks.json  │    │    Safety Scanner  │  │
│  │    CLAUDE.md      │    │    progress.md     │    │    Schema Validator│  │
│  └─────────┬─────────┘    └──────────┬─────────┘    └──────────┬─────────┘  │
│            │                         │                         │            │
│            ▼                         ▼                         ▼            │
│  ┌───────────────────┐    ┌──────────────────────────────────────────────┐  │
│  │ 4. SCOPE BOUNDS   │    │ 5. LIFECYCLE & RUNTIME GUARD                 │  │
│  │    1 Task / Turn  │    │    init.sh ──► wp-seo-guard.php ──► Handoff  │  │
│  │    Strict DoD     │    │    Intercepts catastrophic failures at runtime│  │
│  └───────────────────┘    └──────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## 📖 The 10 Lectures

Full texts and interactive documentation available in `docs/lectures/`:

- **[L01: Why AI Agents Fail on WordPress](docs/lectures/lecture-01-why-ai-agents-fail-on-wordpress.md)** — The paradox of model intelligence and procedural WordPress global state.
- **[L02: WordPress SEO Threat Model](docs/lectures/lecture-02-the-wordpress-seo-threat-model.md)** — The 6 catastrophic failure modes: sitewide noindex, canonical loops, schema hallucination, and XSS.
- **[L03: The 5 Subsystems for WordPress](docs/lectures/lecture-03-the-five-subsystems-for-wordpress.md)** — Adapting Instructions, State, Verification, Scope, and Lifecycle to WordPress.
- **[L04: Progressive Disclosure & AGENTS.md](docs/lectures/lecture-04-progressive-disclosure-and-agents-md.md)** — Why monolithic 2000-line prompts fail and how to structure modular guidelines.
- **[L05: Multi-Session State Persistence](docs/lectures/lecture-05-multi-session-state-persistence.md)** — Defeating agent amnesia with `seo_tasks.json` and `progress.md`.
- **[L06: Session Init & WP-CLI](docs/lectures/lecture-06-session-initialization-and-wp-cli.md)** — Establishing environment health before writing a single line of code.
- **[L07: Scope Control & Atomic Tasks](docs/lectures/lecture-07-scope-control-and-atomic-tasks.md)** — Enforcing the 1-task-per-turn invariant and the Definition of Done.
- **[L08: Verification Gates & Evidence](docs/lectures/lecture-08-verification-gates-and-evidence.md)** — Replacing subjective self-assessment with deterministic CLI proof.
- **[L09: Connected Schema Knowledge Graphs](docs/lectures/lecture-09-connected-schema-knowledge-graphs.md)** — Constructing unified Google-compliant `@graph` networks with `@id` cross-referencing.
- **[L10: Runtime Guards & Active Rollback](docs/lectures/lecture-10-runtime-safety-guards-and-rollback.md)** — Deploying Must-Use plugins (`wp-seo-guard.php`) to intercept live faults.

---

## 🧪 4 Hands-On Projects (with Starter & Solution)

Each project includes real, runnable code fixtures in `projects/`:

| Project | Location | What You Learn |
|---|---|---|
| **P01: Prompt-Only vs. Minimal Harness** | `projects/project-01-baseline-vs-harnessed/` | Run the scanner on a vulnerable starter that de-indexes the site, then test the verified solution. |
| **P02: Schema Knowledge Graph Repair** | `projects/project-02-schema-knowledge-graph/` | Catch missing offer prices and dangling `@id` references in broken JSON-LD and fix them. |
| **P03: Runtime Safety Guard Deployment** | `projects/project-03-runtime-safety-guard/` | Simulate real-time interception of homepage noindex and canonical normalization via PHP. |
| **P04: Full Site Audit & Certification** | `tools/audit-harness.sh` | Run the 100-point audit script on any WordPress repo and achieve certified status. |

---

## ⚡ Comparison: Prompt-Only vs. Genisoft WP SEO Harness

| Dimension | Unharnessed Agent (Prompt-Only) | With Genisoft WP SEO Harness |
| :--- | :--- | :--- |
| **Indexing Safety** | High risk of accidental `noindex` on front page | **100% Protected**: Runtime guard intercept + CLI pre-flight check |
| **Canonical URLs** | Hallucinates relative or mixed HTTP/HTTPS links | **Enforced**: Strict HTTPS, trailing slash normalization, absolute URLs |
| **Schema.org Quality** | Fragmented Microdata with missing required fields | **Enterprise Knowledge Graph**: Fully connected `@graph` with `@id` validation |
| **Multi-Session Work** | Forgets prior work, re-does code, loses context | **Zero Memory Loss**: `seo_tasks.json` + `progress.md` state machine |
| **Definition of Done** | Agent declares "Done!" while code fails silently | **Evidence-Based**: Must pass `verify-seo-safety.php` and `schema-graph-validator.js` |
| **WordPress Stability** | Edits parent themes, risks fatal Error 500 (WSOD) | **Sanctuarized**: Restricts work to child themes / plugins with syntax dry-run |

---

## 🚀 Quick Start (In 3 Minutes)

### 1. Clone & Bootstrap
```bash
git clone https://github.com/lefi0528/genisoft-wp-seo-harness.git
cd genisoft-wp-seo-harness
bash init.sh
```

### 2. Run Course Documentation Locally
```bash
npm install
npm run docs:dev
```
*Open http://localhost:5173 to browse the complete interactive curriculum.*

### 3. Deploy Runtime Safety Guard to Your WordPress Site
Copy the zero-dependency guard to your WordPress `mu-plugins` directory:
```bash
cp tools/wp-seo-guard.php /path/to/wordpress/wp-content/mu-plugins/
```

### 4. Run the Verification Suite
```bash
npm run test:all   # Node.js + PHP 8.1+ (self-tests and regression tests)
bash tools/audit-harness.sh
```

---

## 🛠️ Built-in Tool Suite

1. **`schema-graph-validator.js`**: Node.js CLI tool validating Schema.org JSON-LD graphs against Google Search Central Rich Result specs (`WebSite`, `Organization`, `Product`, `Article`, `BreadcrumbList`, `FAQPage`).
2. **`verify-seo-safety.php`**: Standalone, tokenizer-based PHP scanner flagging any `noindex` (wp_robots, X-Robots-Tag, robots meta) not restricted to narrow contexts, HTTP/relative canonicals, and unescaped dynamic output in meta tags.
3. **`wp-seo-guard.php`**: Must-Use Plugin (`mu-plugin`) that neutralizes accidental homepage `noindex` (staging excluded), normalizes canonicals via `get_canonical_url`, and halts self-redirect loops.
4. **`audit-harness.sh`**: Zero-dependency shell audit that rates any WordPress repository from 0 to 100 based on the 5 harness subsystems.
5. **`lint-php.sh`**: Runs `php -l` on every PHP file and fails on any syntax error (used by CI).

---

## 👨‍💻 Author & Engineering Leadership

Created by **Abdelmonem Lefi (Genisoft)**  
*Senior Full-Stack & Technical SEO Engineer | Agentic AI Specialist*

- 🌐 **Portfolio & Case Studies**: [lefi-abdelmonem.com](https://lefi-abdelmonem.com)
- 💼 **Fiverr Pro**: Verified Elite Web Engineering & Technical SEO
- 🐙 **GitHub**: [@lefi0528](https://github.com/lefi0528)

---

## 📄 License

This project is open-source under the **[MIT License](LICENSE)**.
