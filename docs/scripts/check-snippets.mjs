import { readdir, readFile } from "node:fs/promises";
import path from "node:path";
import { fileURLToPath } from "node:url";

const siteDirectory = path.resolve(
    path.dirname(fileURLToPath(import.meta.url)),
    "..",
);
const repositoryDirectory = path.resolve(siteDirectory, "..");
const sourceDirectory = path.join(siteDirectory, "docs");
const snippetsDirectory = path.join(siteDirectory, "snippets");

const normalize = (value) => value.replace(/\r\n?/g, "\n").replace(/\n$/, "");
const extractFences = (markdown) =>
    [...markdown.matchAll(/^```([^\r\n]*)\r?\n([\s\S]*?)^```\s*$/gm)].map(
        (match) => ({
            metadata: match[1].trimEnd(),
            content: normalize(match[2]),
        }),
    );

const listFiles = async (directory, extensionPattern, prefix = "") => {
    const entries = await readdir(directory, { withFileTypes: true });
    const files = [];

    for (const entry of entries) {
        const relativePath = path.posix.join(prefix, entry.name);

        if (entry.isDirectory()) {
            files.push(
                ...(await listFiles(
                    path.join(directory, entry.name),
                    extensionPattern,
                    relativePath,
                )),
            );
        } else if (entry.isFile() && extensionPattern.test(entry.name)) {
            files.push(relativePath);
        }
    }

    return files.sort();
};

const config = await readFile(
    path.join(siteDirectory, "docusaurus.config.ts"),
    "utf8",
);
const defaultLocale = config.match(/defaultLocale:\s*["']([^"']+)["']/)?.[1];
const localeBlock = config.match(/locales:\s*\[([^\]]+)]/)?.[1];
const locales = [...(localeBlock?.matchAll(/["']([^"']+)["']/g) ?? [])].map(
    (match) => match[1],
);

if (!defaultLocale || locales.length === 0) {
    throw new Error("Unable to read the Docusaurus locale configuration.");
}

let versions = [];

try {
    versions = JSON.parse(
        await readFile(path.join(siteDirectory, "versions.json"), "utf8"),
    );
} catch (error) {
    if (error.code !== "ENOENT") {
        throw error;
    }
}

if (!Array.isArray(versions) || versions.some((version) => typeof version !== "string")) {
    throw new Error("versions.json must contain an array of version names.");
}

const documentationVersions = [
    {
        name: "current",
        directory: sourceDirectory,
    },
    ...versions.map((version) => ({
        name: `version-${version}`,
        directory: path.join(
            siteDirectory,
            "versioned_docs",
            `version-${version}`,
        ),
    })),
];

const selectLines = (content, expression, source) => {
    if (!expression) {
        return content;
    }

    const match = expression.match(/^(\d+)(?:-(\d*))?$/);

    if (!match) {
        throw new Error(`${source}: invalid lines expression ${expression}`);
    }

    const start = Number(match[1]);
    const end = match[2] === undefined || match[2] === "" ? undefined : Number(match[2]);
    const lines = content.replace(/\r\n?/g, "\n").split("\n");

    if (start < 1 || start > lines.length || (end !== undefined && end < start)) {
        throw new Error(`${source}: lines expression ${expression} is out of range`);
    }

    return lines.slice(start - 1, end).join("\n");
};

const resolveSource = (source) => {
    const sourcePath = path.resolve(repositoryDirectory, source);
    const relativePath = path.relative(repositoryDirectory, sourcePath);

    if (relativePath.startsWith("..") || path.isAbsolute(relativePath)) {
        throw new Error(`Snippet source is outside the repository: ${source}`);
    }

    return sourcePath;
};

const markdownFiles = await listFiles(sourceDirectory, /\.mdx?$/);
const referencedSources = new Set();
const errors = [];

for (const markdownFile of markdownFiles) {
    const markdownPath = path.join(sourceDirectory, markdownFile);
    const markdown = await readFile(markdownPath, "utf8");
    const fences = extractFences(markdown);

    for (const fence of fences) {
        const metadata = fence.metadata;
        const source = metadata.match(/\bsource="([^"]+)"/)?.[1];

        if (!source) {
            continue;
        }

        const lines = metadata.match(/\blines="([^"]+)"/)?.[1];
        const sourcePath = resolveSource(source);
        const expected = selectLines(await readFile(sourcePath, "utf8"), lines, source);
        const actual = fence.content;

        referencedSources.add(source.replace(/\\/g, "/"));

        if (normalize(actual) !== normalize(expected)) {
            errors.push(`${markdownFile}: embedded code differs from ${source}`);
        }
    }
}

let localizedPages = 0;

for (const documentationVersion of documentationVersions) {
    const englishFiles = await listFiles(
        documentationVersion.directory,
        /\.mdx?$/,
    );

    for (const locale of locales.filter((locale) => locale !== defaultLocale)) {
        const localizedDirectory = path.join(
            siteDirectory,
            "i18n",
            locale,
            "docusaurus-plugin-content-docs",
            documentationVersion.name,
        );

        for (const markdownFile of englishFiles) {
            const localizedPath = path.join(localizedDirectory, markdownFile);

            try {
                const [english, localized] = await Promise.all([
                    readFile(
                        path.join(documentationVersion.directory, markdownFile),
                        "utf8",
                    ),
                    readFile(localizedPath, "utf8"),
                ]);
                const englishFences = extractFences(english);
                const localizedFences = extractFences(localized);

                localizedPages += 1;

                if (englishFences.length !== localizedFences.length) {
                    errors.push(
                        `${locale}/${documentationVersion.name}/${markdownFile}: fenced code block count changed`,
                    );
                    continue;
                }

                for (let index = 0; index < englishFences.length; index += 1) {
                    if (
                        englishFences[index].metadata !==
                            localizedFences[index].metadata ||
                        englishFences[index].content !==
                            localizedFences[index].content
                    ) {
                        errors.push(
                            `${locale}/${documentationVersion.name}/${markdownFile}: fenced code block ${index + 1} changed`,
                        );
                    }
                }
            } catch (error) {
                if (error.code === "ENOENT") {
                    errors.push(
                        `${locale}/${documentationVersion.name}/${markdownFile}: localized page is missing`,
                    );
                    continue;
                }

                throw error;
            }
        }
    }
}

const snippetFiles = await listFiles(snippetsDirectory, /./);

for (const snippetFile of snippetFiles) {
    const source = path.posix.join("docs/snippets", snippetFile);

    if (!referencedSources.has(source)) {
        errors.push(`Unreferenced documentation snippet: ${source}`);
    }
}

if (errors.length > 0) {
    throw new Error(
        `Documentation snippets are inconsistent:\n${errors.map((error) => `- ${error}`).join("\n")}`,
    );
}

console.log(
    `Documentation snippets match ${referencedSources.size} sources across ${markdownFiles.length} English and ${localizedPages} localized pages.`,
);
