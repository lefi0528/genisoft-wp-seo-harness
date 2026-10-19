# Lecture 01: Why AI Agents Fail on WordPress

## The Paradox of Agent Intelligence

Modern foundation models possess remarkable programming knowledge: they write complex algorithms in Python, TypeScript, and Rust that usually work on the first try.

Yet, when placed in a real WordPress repository, these same models frequently:
1. Trigger the **White Screen of Death (WSOD)** by introducing a fatal PHP syntax error in `functions.php`.
2. Overwrite parent theme templates instead of creating child theme overrides.
3. Invert conditional logic inside WordPress filters, turning an intended single-page noindex into a sitewide indexing disaster.
4. Hallucinate hook execution orders, registering actions too late (e.g., calling `wp_head` hooks inside `wp_footer`).

This failure has nothing to do with model capability. **It is an environment and harness failure.**

---

## 1. WordPress is a Globally Mutable Procedural System

Unlike modern component-based frameworks (React, Svelte) where state and side effects are encapsulated, WordPress is fundamentally built on:
- Global hook registries (`$wp_filter`).
- Global query state (`$wp_query`).
- Runtime constants (`WP_DEBUG`, `ABSPATH`).
- Procedural lifecycle events (`init` ──► `wp_loaded` ──► `template_redirect` ──► `wp_head`).

When an unharnessed agent modifies a file, it cannot "see" the global side-effects unless forced to execute deterministic verification tools.

---

## 2. The Cost of Free-Form Prompts

In a prompt-only workflow, you give instructions like:
> *"Optimize the SEO and structured data of my WordPress theme."*

The model attempts to do everything at once:
- Edits 7 files simultaneously.
- Modifies theme templates directly.
- Writes Microdata in one place, JSON-LD in another.
- Breaks trailing slashes on canonical URLs.
- Says: *"All SEO optimizations completed successfully!"*

When you reload the site, you get an **Error 500**, or Google Search Console reports a massive spike in redirect errors 7 days later.

---

## 3. The Core Tenet of Harness Engineering

> **The MODEL decides what code to write.  
> The HARNESS governs when, where, and how it writes it.**

In the next lecture, we break down the specific **SEO Threat Model** that every WordPress harness must defend against.
