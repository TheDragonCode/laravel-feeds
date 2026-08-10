import { mkdir, readdir, readFile, writeFile } from "node:fs/promises";
import path from "node:path";
import { fileURLToPath } from "node:url";

const documentIds = [
    "introduction",
    "installation",
    "create-feeds",
    "performance",
    "generation",
    "feed-targets",
    "supported-formats",
    "elements",
    "directives",
    "eloquent",
    "location",
    "presets",
    "events",
    "extending-functionality",
    "receipt-target-feeds",
    "receipt-sitemap",
    "receipt-instagram",
    "receipt-yandex",
    "receipt-rss-atom",
    "api/index",
    "api/configuration",
    "api/feed",
    "api/runtime",
    "api/events-exceptions",
    "faq",
    "troubleshooting",
    "upgrade-guide",
    "compatibility",
    "contributions",
    "machine-learning",
    "license",
];
const siteDirectory = path.resolve(
    path.dirname(fileURLToPath(import.meta.url)),
    "..",
);
const sourceDirectory = path.join(siteDirectory, "docs");
const outputDirectory = path.join(siteDirectory, "static");
const outputPath = path.join(outputDirectory, "llms.txt");
const siteUrl = "https://feeds.dragon-code.pro";

const listMarkdownFiles = async (directory) => {
    const entries = await readdir(directory, { withFileTypes: true });
    const files = [];

    for (const entry of entries) {
        const entryPath = path.join(directory, entry.name);

        if (entry.isDirectory()) {
            files.push(...(await listMarkdownFiles(entryPath)));
        } else if (entry.isFile() && /\.mdx?$/.test(entry.name)) {
            files.push(entryPath);
        }
    }

    return files;
};

const stripFrontMatter = (content) =>
    content.replace(/^---\n[\s\S]*?\n---(?:\n|$)/, "");

const stripMdxModules = (content) =>
    content
        .replace(
            /^import\s+[\s\S]*?(?:\sfrom\s*)?["'][^"']+["'];?[ \t]*$/gm,
            "",
        )
        .replace(/^export\s+\{[\s\S]*?\};?[ \t]*$/gm, "");

const replaceThemedImages = (content) =>
    content.replace(
        /<ThemedImage[\s\S]*?alt="([^"]+)"[\s\S]*?light:\s*'([^']+)'[\s\S]*?\/>/g,
        (_, alt, source) => `![${alt}](${siteUrl}${source})`,
    );

const stripNavigation = (content) =>
    content.replace(/^.*\[Back to README]\([^\n]+\).*\n?/gm, "");

const rewriteInternalLinks = (content, documentId) =>
    content.replace(
        /\]\((\.\.?\/[^)#]+\.mdx)(#[^)]+)?\)/g,
        (_, relativePath, fragment = "") => {
            const sourceDirectory = path.posix.dirname(documentId);
            const target = path.posix.normalize(
                path.posix.join(
                    sourceDirectory,
                    relativePath.replace(/\.mdx$/, ""),
                ),
            );
            const route = target === "api/index" ? "/api/" : `/${target}/`;

            return `](${siteUrl}${route}${fragment})`;
        },
    );

const stripExplicitHeadingIds = (content) =>
    content.replace(/\s*\{\/\*\s*#[^*]+\*\/\}/g, "");

const stripFenceMetadata = (content) =>
    content
        .split("\n")
        .map((line) => {
            const fence = line.match(/^(\s*)(`{3,}|~{3,})(.*)$/);

            if (!fence) {
                return line;
            }

            const info = fence[3]
                .replace(
                    /\s+(?:source|lines)=(?:"[^"]*"|'[^']*'|[^\s]+)/g,
                    "",
                )
                .trimEnd();

            return `${fence[1]}${fence[2]}${info}`;
        })
        .join("\n");

const prepareDocument = (content, documentId) =>
    stripFenceMetadata(
        stripExplicitHeadingIds(
            rewriteInternalLinks(
                stripNavigation(
                    replaceThemedImages(
                        stripMdxModules(
                            stripFrontMatter(content.replace(/^\uFEFF/, "")),
                        ),
                    ),
                ),
                documentId,
            ),
        ),
    ).trim();

const markdownFiles = await listMarkdownFiles(sourceDirectory);
const documentsById = new Map();

for (const markdownFile of markdownFiles) {
    const id = path
        .relative(sourceDirectory, markdownFile)
        .replace(/\\/g, "/")
        .replace(/\.mdx?$/, "");

    if (documentsById.has(id)) {
        throw new Error(`Duplicate documentation id: ${id}`);
    }

    documentsById.set(id, markdownFile);
}

const missingIds = documentIds.filter((id) => !documentsById.has(id));
const unexpectedIds = [...documentsById.keys()].filter(
    (id) => !documentIds.includes(id),
);

if (missingIds.length > 0) {
    throw new Error(`Missing documentation pages: ${missingIds.join(", ")}`);
}

if (unexpectedIds.length > 0) {
    throw new Error(
        `Documentation pages missing from the llms.txt order: ${unexpectedIds.join(", ")}`,
    );
}

const documents = [];

for (const id of documentIds) {
    const content = await readFile(documentsById.get(id), "utf8");

    documents.push(
        prepareDocument(content.replace(/\r\n?/g, "\n"), id),
    );
}

const header = `# Laravel Feeds

> Fast export of large datasets to feeds for marketplaces and services.

Source: https://github.com/TheDragonCode/laravel-feeds
Documentation: https://feeds.dragon-code.pro`;
const output = `${[header, ...documents].join("\n\n---\n\n")}\n`;

if (/\]\([^)]*\.mdx(?:#[^)]+)?\)/.test(output)) {
    throw new Error("Generated llms.txt contains unresolved MDX links.");
}

await mkdir(outputDirectory, { recursive: true });
await writeFile(outputPath, output, "utf8");

console.log(`Generated ${path.relative(siteDirectory, outputPath)} from ${documents.length} pages.`);
