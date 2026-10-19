import { defineConfig } from 'vitepress';

export default defineConfig({
  title: "Genisoft WP SEO Harness",
  description: "Autonomous WordPress Technical SEO & Core Web Vitals Engineering Harness for AI Coding Agents",
  base: "/genisoft-wp-seo-harness/",
  themeConfig: {
    nav: [
      { text: "Home", link: "/" },
      { text: "Lectures", link: "/lectures/" },
      { text: "Projects", link: "/projects/" },
      { text: "Resources", link: "/resources/" },
      { text: "GitHub", link: "https://github.com/lefi0528/genisoft-wp-seo-harness" }
    ],
    sidebar: {
      "/lectures/": [
        {
          text: "Theory & Curriculum",
          items: [
            { text: "Course Overview", link: "/lectures/" },
            { text: "L01: Why Agents Fail on WordPress", link: "/lectures/lecture-01-why-ai-agents-fail-on-wordpress" },
            { text: "L02: WordPress SEO Threat Model", link: "/lectures/lecture-02-the-wordpress-seo-threat-model" },
            { text: "L03: The 5 Subsystems for WP", link: "/lectures/lecture-03-the-five-subsystems-for-wordpress" },
            { text: "L04: Progressive Disclosure & AGENTS.md", link: "/lectures/lecture-04-progressive-disclosure-and-agents-md" },
            { text: "L05: Multi-Session State Persistence", link: "/lectures/lecture-05-multi-session-state-persistence" },
            { text: "L06: Session Init & WP-CLI", link: "/lectures/lecture-06-session-initialization-and-wp-cli" },
            { text: "L07: Scope Control & Atomic Tasks", link: "/lectures/lecture-07-scope-control-and-atomic-tasks" },
            { text: "L08: Verification Gates & Evidence", link: "/lectures/lecture-08-verification-gates-and-evidence" },
            { text: "L09: Connected Schema Knowledge Graph", link: "/lectures/lecture-09-connected-schema-knowledge-graphs" },
            { text: "L10: Runtime Guards & Active Rollback", link: "/lectures/lecture-10-runtime-safety-guards-and-rollback" }
          ]
        }
      ],
      "/projects/": [
        {
          text: "Hands-on Projects",
          items: [
            { text: "Projects Overview", link: "/projects/" },
            { text: "P01: Prompt-Only vs. Harnessed", link: "/projects/project-01-prompt-only-vs-harnessed" },
            { text: "P02: Schema Knowledge Graph Repair", link: "/projects/project-02-schema-knowledge-graph-repair" },
            { text: "P03: Runtime Safety Guard Deployment", link: "/projects/project-03-runtime-safety-guard-deployment" },
            { text: "P04: Full Site Audit & Certification", link: "/projects/project-04-full-site-audit-and-certification" }
          ]
        }
      ],
      "/resources/": [
        {
          text: "Resource Library",
          items: [
            { text: "Overview", link: "/resources/" },
            { text: "Templates & Configs", link: "/resources/templates" },
            { text: "Technical References", link: "/resources/reference" }
          ]
        }
      ]
    },
    socialLinks: [
      { icon: "github", link: "https://github.com/lefi0528/genisoft-wp-seo-harness" }
    ],
    footer: {
      message: "Released under the MIT License.",
      copyright: "Copyright © 2026 Genisoft / Abdelmonem Lefi"
    }
  }
});
