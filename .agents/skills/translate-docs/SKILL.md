---
name: translate-docs
description: >-
  Translate, complete, and audit Docusaurus localization under docs/i18n for
  both UI JSON catalogs and documentation Markdown/MDX pages. Use when adding
  or correcting locales, when a language switcher translates only theme labels
  while page content stays in the default language, or when verifying that
  non-default locales do not fall back to source documentation.
---

# Docusaurus Documentation Translation

Translate the locale codes requested in `$ARGUMENTS`.

When no locale is supplied, process every configured non-default locale whose
root directory already exists below `docs/i18n`. Treat `all` the same way. Use
the default locale only as the semantic source.

## Translation Surfaces

Treat localization as two separate surfaces:

1. UI catalogs: existing JSON files below `docs/i18n/<locale>`.
2. Page content: Markdown and MDX files below
   `docs/i18n/<locale>/docusaurus-plugin-content-docs/current`, matched by
   relative path to `docs/docs`.

`docusaurus write-translations` generates UI catalogs. It does not prove that
page content is localized. Missing content pages cause Docusaurus to use the
default-language pages.

## Ownership

For each selected non-default locale, edit only:

- string values of `message` and existing `description` fields in existing
  JSON localization files;
- localized `.md` and `.mdx` files below
  `docusaurus-plugin-content-docs/current`.

Create the localized content directory and missing page files when the locale
root exists. Never create a missing locale root or invent JSON catalogs.

Treat these paths as read-only context:

- `docs/docusaurus.config.ts`
- every source page below `docs/docs`
- the default locale
- locales outside the selected scope
- generated output such as `docs/build`

Preserve pre-existing user changes. Never use `git checkout`, `git restore`,
`git reset`, or file-wide regeneration to undo them.

## Hard Invariants

Apply these rules to every translated value:

- use only the language and regional variant declared in
  `i18n.localeConfigs`;
- preserve placeholders, URLs, inline code, commands, paths, identifiers,
  package names, class names, method names, HTML/JSX tags, and MDX expressions;
- preserve intentional leading or trailing whitespace;
- translate meaning rather than source-language word order;
- keep existing correct translations instead of rewriting them for style.

For JSON catalogs:

1. Keep filenames, relative paths, translation keys, and key order unchanged.
2. Keep entry fields, field order, nesting, and value types unchanged.
3. Edit only `message` and an existing `description`.
4. Never add a missing `description`.
5. Preserve Docusaurus plural syntax and every placeholder. Allow repeated
   placeholders only when the target locale needs a different number of plural
   forms.
6. Preserve encoding, indentation, line endings, and the final newline.

The JSON key is an identifier. Never translate it.

For Markdown and MDX pages:

1. Match the complete source page set and relative paths from `docs/docs`.
2. Translate prose, headings, table text, list text, navigation labels, link
   labels, image alt text, and translatable front matter.
3. In front matter, translate only `title`, `description`, `sidebar_label`, and
   `pagination_label`. Keep every other field and value unchanged.
4. Preserve fenced code blocks and their language markers exactly, apart from
   file-wide newline normalization.
5. Preserve heading hierarchy, table shape, list shape, link destinations,
   reference-link identifiers, imports, exports, and component structure.
6. Keep relative links relative. Never rewrite a destination merely because
   its label is translated.
7. Do not leave ordinary source-language prose or copy an entire source page
   unchanged into a non-default locale.
8. Preserve an existing file's encoding, line endings, and final newline. Use
   UTF-8 with a final newline for a new localized page.

Do not delete unexpected locale-only pages automatically. Report them and do
not claim the locale is complete until the mismatch is resolved.

## Workflow

### 1. Resolve the Locale Scope

Read `docs/docusaurus.config.ts` and locate:

- `i18n.defaultLocale`
- `i18n.locales`
- `i18n.localeConfigs`
- each locale's `label` and `htmlLang`
- the docs source path and content plugin instance

Use `localeConfigs` as the authority for the target language. Respect regional
variants such as Brazilian Portuguese and Simplified Chinese.

Resolve requested locale codes against both configuration and
`docs/i18n/<locale>`. If a configured locale root is missing, report and skip
it. Do not infer a language from a directory name alone.

Record `git status --short` before editing so existing changes remain visible.

### 2. Inventory Both Surfaces

Before editing a locale:

1. Enumerate every source `.md` and `.mdx` page recursively below `docs/docs`.
2. Enumerate every JSON file in the locale recursively.
3. Enumerate localized pages below
   `docusaurus-plugin-content-docs/current`.
4. Compare source and localized relative page paths.
5. Treat a missing page or a page identical to the source as incomplete
   localization, even if all JSON catalogs are translated.

Read every source page in full and build a consistent glossary for project
concepts and public API names. Then read the selected locale's existing JSON
and localized pages before changing them.

Use matching default-locale JSON entries when they exist. Otherwise derive UI
meaning from the translation key and documentation context. Never invent
product behavior.

### 3. Capture the Structural Baseline

Find a working Python 3 interpreter. Try `python3 --version`,
`python --version`, `py -3 --version`, then `py --version`, and use the first
command that reports Python 3.

Create one baseline per locale immediately before editing it:

```text
python .agents/skills/translate-docs/scripts/i18n_guard.py snapshot --root docs/i18n --source-root docs/docs --locale <locale>
```

Replace `python` with the resolved interpreter command when needed. Record the
exact path printed after `Snapshot:`. Snapshot creation is valid when localized
Markdown pages are missing; those pages become required outputs. If snapshot
creation fails, do not edit the locale.

### 4. Translate One Locale

Process one locale completely before starting another.

For JSON entries:

- translate every `message`;
- translate an existing `description` with the same UI meaning;
- preserve placeholders and use the plural forms required by the target
  locale;
- use targeted edits instead of serializing whole files.

For documentation pages:

- create each missing target page at the matching relative path;
- use the matching source page as semantic and structural input;
- translate all reader-visible prose while preserving code and technical
  contracts;
- keep terminology consistent with the locale's JSON catalogs and all other
  pages;
- use targeted edits for existing translations.

### 5. Validate Before Moving On

Run the guard with the recorded snapshot:

```text
python .agents/skills/translate-docs/scripts/i18n_guard.py validate --root docs/i18n --snapshot <snapshot-path> --delete-snapshot
```

The guard must pass. It checks directory and file paths, JSON validity,
translation keys and order, entry schemas, non-translatable values, protected
tokens, source and localized page sets, front matter, code blocks, heading
hierarchy, links, tables, lists, and MDX statements. On failure, it retains the
snapshot. Fix only changes made during this workflow and rerun validation.

The guard proves structural preservation and full page coverage. It cannot
prove that every prose fragment uses the target language. Treat the language
review and built-HTML inspection below as separate required gates.

Then:

1. Inspect `git diff -- docs/i18n/<locale>` and reject formatting churn.
2. Run `npm run check:i18n` from `docs` when the script exists.
3. Run the project's TypeScript check when configured.
4. Run a production Docusaurus build for all configured locales.
5. Inspect built HTML for at least one unique translated sentence per locale
   and confirm the source-language sentence is absent.

Perform a language review across every changed value and page:

- language and regional variant match `localeConfigs`;
- ordinary source-language prose does not remain;
- terminology is consistent across UI and page content;
- grammar fits placeholders and plural forms;
- punctuation, navigation wording, and accessibility text are natural;
- technical claims still match the source documentation.

Only after structural and language review pass may processing continue to the
next locale.

### 6. Report the Result

Report:

- processed locale codes and their configured language labels;
- changed JSON files;
- created and changed Markdown/MDX files;
- structural, language, completeness, and production-build results;
- skipped locales and the exact reason for each skip.

Never claim a locale is complete when a source page is missing, a page still
falls back to the source language, or any validation failed.
