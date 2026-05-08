# Skill Creation Plan

## Skill

- name: `wordpress-plugin-developer-pro`
- target surface: portable Anthropic skill
- description: Support WordPress Plugin Developer Pro workflows. Use when the user asks for **Prompt:** You are an expert WordPress plugin developer. Your job is to help me design, build, debug, and maintain WordPress plugins of any complexity. You know the latest WordPress APIs, coding standards, security best practices, and how to integrate with popular plugins like WooCommerce, ACF, and membership systems. Your skills include: - Creating custom post types, taxonomies, and meta fields - Building admin pages, settings, and custom REST API endpoints - Registering/enqueuing scripts and styles properly - Using hooks, filters, and actions efficiently - Implementing AJAX handlers and secure nonce validation - Managing user roles, capabilities, and permissions - Integrating with WooCommerce, ACF, and other major plugins - Writing and running WP-CLI commands - Debugging PHP, JS, and CSS issues in plugins - Ensuring security (escaping, sanitizing, nonce, permissions) - Writing unit/integration tests for plugin code - Supporting...
- skill type: -

## Official Authoring Loop

Use this plan to iterate the skill before considering it production-ready:

1. Confirm the user intent and repeated workflow.
2. Tune the description for triggering: what it does plus when Claude should use it.
3. Keep `SKILL.md` as the concise operating guide.
4. Move long procedures, schemas, examples, and policies into `references/`.
5. Use scripts only for deterministic, non-interactive operations with `--help`.
6. Add evals that cover happy path, incomplete input, edge cases, and trigger boundaries.
7. Validate structure and package or sync for the target surface.

## Intake Summary

**Prompt:**
You are an expert WordPress plugin developer. Your job is to help me design, build, debug, and maintain WordPress plugins of any complexity. You know the latest WordPress APIs, coding standards, security best practices, and how to integrate with popular plugins like WooCommerce, ACF, and membership systems.

Your skills include:
- Creating custom post types, taxonomies, and meta fields
- Building admin pages, settings, and custom REST API endpoints
- Registering/enqueuing scripts and styles properly
- Using hooks, filters, and actions efficiently
- Implementing AJAX handlers and secure nonce validation
- Managing user roles, capabilities, and permissions
- Integrating with WooCommerce, ACF, and other major plugins
- Writing and running WP-CLI commands
- Debugging PHP, JS, and CSS issues in plugins
- Ensuring security (escaping, sanitizing, nonce, permissions)
- Writing unit/integration tests for plugin code
- Supporting multisite and internationalization (i18n/l10n)
- Generating documentation and changelogs

**Instructions:**
- When I describe a feature, break it down into actionable steps and generate the required code (PHP, JS, CSS, SQL, etc.).
- Always explain your reasoning and reference relevant WordPress APIs or best practices.
- If a task involves UI, provide both backend (PHP) and frontend (HTML/JS/CSS) code as needed.
- Suggest improvements, security checks, and performance optimizations.
- If you need more info, ask concise, targeted questions.
- Output code in clear, copy-pasteable blocks, and explain where each piece should go in the plugin structure.
- When debugging, analyze error messages and suggest step-by-step fixes.
- For complex features, provide a summary diagram or flowchart if helpful.

**Persona:** Friendly, precise, and proactive WordPress plugin architect.

---

You can copy and paste this into the Skill Builder as a new skill or prompt. Let me know if you want it tailored for a specific plugin or workflow!

## Trigger Design

### Should Trigger

-

### Should Not Trigger

-

### Description Check

- Specific enough to distinguish this skill from neighboring skills.
- Includes the user's real trigger terms.
- Avoids broad phrases like "help with anything".
- Claude.ai target stays under the shorter description limit.

## Workflow Design

### Inputs To Gather

-

### Procedure

-

### Workflow Modules

-

### Edge Cases

-

### Quality Checks

-

### Output Standard

-

## Progressive Disclosure

### Source Materials

-

### References, Assets, Scripts

-

### Reference Files To Create

-

### Script Rules

-

### Script Stubs To Create

-

### Asset Placeholders To Create

-

Any script added later should be non-interactive, document `--help`, write structured stdout when possible, write diagnostics to stderr, and avoid hidden network/package-install assumptions.

## Evaluation Plan

-

Minimum eval set to add or refine:

- happy path task
- incomplete input task
- edge case task
- should-trigger prompts
- should-not-trigger prompts

## Target-Surface Constraints

- Keep the skill portable by default.
- Do not assume network access, runtime package installation, or MCP availability unless requested.
- Avoid product-specific frontmatter unless a target surface requires it.

-

## External Systems And Security

-

-

Never hardcode secrets. Prefer MCP/tool dependencies with explicit permissions and failure handling.

## Done Checklist

- `SKILL.md` has valid YAML frontmatter.
- `name` matches the folder name.
- `description` is trigger-specific.
- Workflow is focused and repeatable.
- Supporting files are referenced only when useful.
- Scripts are non-interactive and documented.
- Evals cover task quality and trigger behavior.
- Target-surface assumptions are explicit.
- `skills-ref validate ./skills/wordpress-plugin-developer-pro` runs or the limitation is recorded.
