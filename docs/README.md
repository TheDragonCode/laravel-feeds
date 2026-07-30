# Laravel Feeds documentation

This directory contains the Docusaurus site for `https://feeds.dragon-code.pro`.

## Local development

Use Node.js 24 and install the locked dependencies:

```bash
npm ci
npm start
```

Run the same checks used by CI before committing documentation changes:

```bash
npm run typecheck
npm run check:snippets
npm run check:i18n
npm run build
```

The production build contains every configured locale.

## Search indexing

The site uses the existing Algolia DocSearch application configured in `docusaurus.config.ts`. The crawler configuration is managed outside this repository; the deployment workflow does not publish a search index.

After deploying a structural or content change, update the crawler to the official [Docusaurus v2 and v3 template](https://docsearch.algolia.com/docs/templates/#docusaurus-v2--v3-template) and trigger a new crawl from the Algolia dashboard. Search results are not reliable until that crawl finishes.

## Content layout

- `docs/` contains the current English source pages.
- `i18n/<locale>/docusaurus-plugin-content-docs/current/` contains localized pages.
- `i18n/<locale>/*.json` contains localized UI messages.
- `snippets/` contains examples generated and checked by the PHP test suite.
- `static/` contains site-wide assets that do not change between documentation versions.

English is the default locale. The complete locale set is `be`, `de`, `en`, `fr`, `ko`, `pt-BR`, `ru`, `uk`, and `zh-CN`. The `zh-CN` locale uses Simplified Chinese.

## Updating content

Update the English page first. Then update the matching page in every non-default locale without changing code blocks, links, heading IDs, or MDX structure.

Regenerate UI catalogs after changing navigation, footer, or sidebar labels:

```bash
npm run translate
```

The command generates catalogs. It does not translate documentation pages. Complete the JSON messages and localized MDX files before running `npm run check:i18n`.

Code fences with a `source` field contain a version-frozen copy of a tracked example. Run `npm run check:snippets` after changing either the source file or the embedded block.

## Creating a documentation version

Finish and validate all current locale changes before creating a snapshot:

```bash
npm run check:snippets
npm run check:i18n
npm run docs:version -- 1.15
npm run build
```

The wrapper validates the current content, runs the Docusaurus version command for every locale, corrects the localized version labels, and validates the frozen result. It creates `versioned_docs/`, `versioned_sidebars/`, `versions.json`, and matching localized version directories. Create versions only when maintained release lines require different documentation.

The current documentation stays under `docs/` and each locale's `current/` directory. A version snapshot must remain immutable except for factual or security corrections that also need to apply to that release line.
