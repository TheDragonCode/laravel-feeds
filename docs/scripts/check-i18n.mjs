import { mkdtemp, readdir, readFile, rm } from "node:fs/promises";
import { tmpdir } from "node:os";
import path from "node:path";
import { spawnSync } from "node:child_process";
import { fileURLToPath } from "node:url";

const siteDirectory = path.resolve(
    path.dirname(fileURLToPath(import.meta.url)),
    "..",
);
const config = await readFile(
    path.join(siteDirectory, "docusaurus.config.ts"),
    "utf8",
);
const defaultLocale = config.match(/defaultLocale:\s*["']([^"']+)["']/)?.[1];
const localeBlock = config.match(/locales:\s*\[([^\]]+)]/)?.[1];
const locales = [...(localeBlock?.matchAll(/["']([^"']+)["']/g) ?? [])].map(
    (match) => match[1],
);
const translatedLocales = locales.filter((locale) => locale !== defaultLocale);

if (!defaultLocale || translatedLocales.length === 0) {
    throw new Error("Unable to read the Docusaurus locale configuration.");
}

const normalize = (content) =>
    content.replace(/^\uFEFF/, "").replace(/\r\n?/g, "\n").trim();

const listFiles = async (directory, pattern, prefix = "") => {
    const entries = await readdir(directory, { withFileTypes: true });
    const files = [];

    for (const entry of entries) {
        const relativePath = path.posix.join(prefix, entry.name);

        if (entry.isDirectory()) {
            files.push(
                ...(await listFiles(
                    path.join(directory, entry.name),
                    pattern,
                    relativePath,
                )),
            );
        } else if (entry.isFile() && pattern.test(entry.name)) {
            files.push(relativePath);
        }
    }

    return files.sort();
};

const parseJson = async (filename) => {
    const value = JSON.parse(await readFile(filename, "utf8"));

    if (!value || typeof value !== "object" || Array.isArray(value)) {
        throw new Error(`${filename} must contain an object.`);
    }

    return value;
};

const protectedTokens = (value) =>
    [
        ...new Set([
            ...(value.match(/\{[^{}]+\}/g) ?? []),
            ...(value.match(/https?:\/\/[^\s)"']+/g) ?? []),
            ...(value.match(/©\s*\d{4}/g) ?? []),
            ...(value.match(/\b\d+(?:\.(?:\d+|x))+(?:[-A-Za-z0-9.]*)?\b/g) ?? []),
        ]),
    ].sort();

const markdownSignature = (content) => {
    const normalized = normalize(content);
    const withoutFences = normalized.replace(
        /^```[^\r\n]*\r?\n[\s\S]*?^```\s*$/gm,
        "",
    );
    const frontMatter = withoutFences.match(/^---\n([\s\S]*?)\n---/)?.[1] ?? "";

    return {
        frontMatterKeys: [...frontMatter.matchAll(/^([A-Za-z0-9_-]+):/gm)].map(
            (match) => match[1],
        ),
        headingLevels: [...withoutFences.matchAll(/^(#{1,6})\s+/gm)].map(
            (match) => match[1].length,
        ),
        linkTargets: [...withoutFences.matchAll(/\]\(([^)]+)\)/g)].map(
            (match) => match[1],
        ),
        explicitAnchors:
            withoutFences.match(/\{\/\*\s*#[^*]+\*\/\}/g) ?? [],
        mdxStatements:
            withoutFences.match(/^(?:import|export)\s+.*$/gm) ?? [],
        admonitions: [...withoutFences.matchAll(/^:::(\w+)/gm)].map(
            (match) => match[1],
        ),
    };
};

const neutralTerms = [
    "Andrey Helldar",
    "Laravel Feeds",
    "Laravel Idea",
    "Spatie Laravel Data",
    "The Dragon Code",
    "FeedInfo",
    "FeedItem",
    "ElementData",
    "FeedFormatEnum",
    "GeneratorService",
    "FeedQuery",
    "ScheduleFeedHelper",
    "Transformer",
    "OptionalData",
    "Macroable",
    "PhpStorm",
    "Composer",
    "Configuration",
    "Copyright",
    "Directives",
    "Eloquent",
    "GitHub",
    "Instagram",
    "Installation",
    "JSONL",
    "Laravel",
    "License",
    "Links",
    "Manual",
    "Queue",
    "README",
    "Sitemap",
    "Yandex",
    "Atom",
    "Boosty",
    "CSV",
    "Feed",
    "JSON",
    "MIT",
    "PHP",
    "RSS",
    "Telegram",
    "XML",
];

const isNeutralSourceLine = (line) => {
    let value = line
        .trim()
        .replace(/^(?:title|description|sidebar_label):\s*/, "")
        .replace(/`[^`]+`/g, "")
        .replace(/https?:\/\/[^\s)]+/g, "")
        .replace(/\]\([^)]+\)/g, "]")
        .replace(/<[^>]+>/g, "")
        .replace(/\{\/\*\s*#[^*]+\*\/\}/g, "")
        .replace(/@[A-Za-z][A-Za-z0-9_-]*/g, "");

    for (const term of neutralTerms) {
        value = value.replaceAll(term, "");
    }

    return !/[A-Za-z]{3,}/.test(value);
};

const translatableLines = (content) => {
    const withoutFences = normalize(content).replace(
        /^```[^\r\n]*\r?\n[\s\S]*?^```\s*$/gm,
        "",
    );

    return withoutFences
        .split("\n")
        .map((line) => line.trim())
        .filter(
            (line) =>
                line &&
                line !== "---" &&
                line !== "slug: /" &&
                !/^(?:slug|type|status|since|keywords):/.test(line) &&
                !line.startsWith("import ") &&
                !line.startsWith("export ") &&
                !line.startsWith("alt=") &&
                !line.startsWith("<") &&
                !line.startsWith(":::") &&
                !line.startsWith("sources={{") &&
                !line.startsWith("light:") &&
                !line.startsWith("dark:") &&
                line !== "}}" &&
                line !== "/>" &&
                /[A-Za-z]/.test(line),
        );
};

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

const sourceVersions = [
    {
        name: "current",
        directory: path.join(siteDirectory, "docs"),
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

for (const sourceVersion of sourceVersions) {
    sourceVersion.files = await listFiles(sourceVersion.directory, /\.mdx?$/);
}

const extractExpectedCatalogs = async () => {
    const temporaryDirectory = await mkdtemp(
        path.join(tmpdir(), "laravel-feeds-i18n-"),
    );
    const referenceLocale = translatedLocales[0];

    try {
        const result = spawnSync(
            process.execPath,
            [
                path.join(
                    siteDirectory,
                    "node_modules",
                    "@docusaurus",
                    "core",
                    "bin",
                    "docusaurus.mjs",
                ),
                "write-translations",
                "--locale",
                referenceLocale,
                "--override",
            ],
            {
                cwd: siteDirectory,
                encoding: "utf8",
                env: {
                    ...process.env,
                    DOCUSAURUS_I18N_PATH: temporaryDirectory,
                },
            },
        );

        if (result.error) {
            throw result.error;
        }

        if (result.status !== 0) {
            throw new Error(
                `Unable to extract Docusaurus translation catalogs:\n${result.stderr || result.stdout}`,
            );
        }

        const catalogRoot = path.join(temporaryDirectory, referenceLocale);
        const catalogPaths = await listFiles(catalogRoot, /\.json$/);
        const catalogs = new Map();

        for (const catalogPath of catalogPaths) {
            catalogs.set(
                catalogPath,
                await parseJson(path.join(catalogRoot, catalogPath)),
            );
        }

        return catalogs;
    } finally {
        await rm(temporaryDirectory, { recursive: true, force: true });
    }
};

const expectedCatalogs = await extractExpectedCatalogs();
const expectedCatalogPaths = [...expectedCatalogs.keys()].sort();
const errors = [];

const validateCatalog = async (locale, catalogPath) => {
    const filename = path.join(siteDirectory, "i18n", locale, catalogPath);
    const expected = expectedCatalogs.get(catalogPath);
    let actual;

    try {
        actual = await parseJson(filename);
    } catch (error) {
        errors.push(`${locale}: invalid UI catalog ${catalogPath}: ${error.message}`);
        return;
    }

    const expectedKeys = Object.keys(expected).sort();
    const actualKeys = Object.keys(actual).sort();

    if (JSON.stringify(expectedKeys) !== JSON.stringify(actualKeys)) {
        errors.push(`${locale}: translation key set or order changed in ${catalogPath}`);
        return;
    }

    for (const key of expectedKeys) {
        const expectedEntry = expected[key];
        const actualEntry = actual[key];

        if (
            !actualEntry ||
            typeof actualEntry !== "object" ||
            Array.isArray(actualEntry)
        ) {
            errors.push(`${locale}: invalid entry ${catalogPath}:${key}`);
            continue;
        }

        const expectedFields = Object.keys(expectedEntry).sort();
        const actualFields = Object.keys(actualEntry).sort();

        if (JSON.stringify(expectedFields) !== JSON.stringify(actualFields)) {
            errors.push(`${locale}: entry fields changed in ${catalogPath}:${key}`);
            continue;
        }

        for (const field of expectedFields) {
            if (typeof actualEntry[field] !== "string" || !actualEntry[field]) {
                errors.push(`${locale}: invalid ${catalogPath}:${key}.${field}`);
                continue;
            }

            if (
                JSON.stringify(protectedTokens(expectedEntry[field])) !==
                JSON.stringify(protectedTokens(actualEntry[field]))
            ) {
                errors.push(
                    `${locale}: protected tokens changed in ${catalogPath}:${key}.${field}`,
                );
            }
        }
    }
};

for (const locale of translatedLocales) {
    const localeDirectory = path.join(siteDirectory, "i18n", locale);
    const actualCatalogPaths = await listFiles(localeDirectory, /\.json$/);

    if (
        JSON.stringify(expectedCatalogPaths) !==
        JSON.stringify(actualCatalogPaths)
    ) {
        errors.push(`${locale}: UI catalog file set changed`);
    }

    for (const catalogPath of expectedCatalogPaths) {
        await validateCatalog(locale, catalogPath);
    }

    for (const sourceVersion of sourceVersions) {
        const localizedDirectory = path.join(
            localeDirectory,
            "docusaurus-plugin-content-docs",
            sourceVersion.name,
        );
        let localizedFiles = [];

        try {
            localizedFiles = await listFiles(localizedDirectory, /\.mdx?$/);
        } catch (error) {
            if (error.code === "ENOENT") {
                errors.push(
                    `${locale}: localized docs directory ${sourceVersion.name} is missing`,
                );
                continue;
            }

            throw error;
        }

        const missingFiles = sourceVersion.files.filter(
            (file) => !localizedFiles.includes(file),
        );
        const extraFiles = localizedFiles.filter(
            (file) => !sourceVersion.files.includes(file),
        );

        if (missingFiles.length > 0) {
            errors.push(
                `${locale}/${sourceVersion.name}: missing ${missingFiles.join(", ")}`,
            );
        }

        if (extraFiles.length > 0) {
            errors.push(
                `${locale}/${sourceVersion.name}: unexpected ${extraFiles.join(", ")}`,
            );
        }

        for (const file of sourceVersion.files.filter((file) =>
            localizedFiles.includes(file),
        )) {
            const [source, localized] = await Promise.all([
                readFile(path.join(sourceVersion.directory, file), "utf8"),
                readFile(path.join(localizedDirectory, file), "utf8"),
            ]);
            const sourceText = normalize(source);
            const localizedText = normalize(localized);

            if (sourceText === localizedText) {
                errors.push(
                    `${locale}/${sourceVersion.name}: ${file} is identical to the English source`,
                );
                continue;
            }

            const sourceSignature = markdownSignature(sourceText);
            const localizedSignature = markdownSignature(localizedText);

            for (const field of Object.keys(sourceSignature)) {
                if (
                    JSON.stringify(sourceSignature[field]) !==
                    JSON.stringify(localizedSignature[field])
                ) {
                    errors.push(
                        `${locale}/${sourceVersion.name}/${file}: ${field} changed`,
                    );
                }
            }

            const localizedLineSet = new Set(translatableLines(localizedText));
            const untranslatedLines = translatableLines(sourceText).filter(
                (line) =>
                    localizedLineSet.has(line) && !isNeutralSourceLine(line),
            );

            if (untranslatedLines.length > 0) {
                errors.push(
                    `${locale}/${sourceVersion.name}/${file}: untranslated source lines: ${untranslatedLines.join(" | ")}`,
                );
            }
        }
    }
}

if (errors.length > 0) {
    throw new Error(
        `Incomplete documentation localization:\n${errors.map((error) => `- ${error}`).join("\n")}`,
    );
}

const versionLabel =
    sourceVersions.length === 1 ? "documentation version" : "documentation versions";

console.log(
    `Documentation localization is complete for ${translatedLocales.length} locales and ${sourceVersions.length} ${versionLabel}.`,
);
