import { mkdir, readdir, readFile, writeFile } from "node:fs/promises";
import path from "node:path";
import { fileURLToPath } from "node:url";

const siteDirectory = path.resolve(
    path.dirname(fileURLToPath(import.meta.url)),
    "..",
);
const docsDirectory = path.join(siteDirectory, "docs");
const configPath = path.join(siteDirectory, "docusaurus.config.ts");
const manifestPath = path.join(siteDirectory, "routes.json");
const searchDirectory = path.join(siteDirectory, "static", "search");
const requiredFields = [
    "title",
    "description",
    "type",
    "status",
    "since",
    "keywords",
];
const allowedTypes = new Set([
    "concept",
    "guide",
    "project",
    "recipe",
    "reference",
]);
const allowedStatuses = new Set(["draft", "stable", "deprecated"]);

const listPages = async (directory, prefix = "") => {
    const entries = await readdir(directory, { withFileTypes: true });
    const pages = [];

    for (const entry of entries) {
        const relativePath = path.posix.join(prefix, entry.name);

        if (entry.isDirectory()) {
            pages.push(
                ...(await listPages(
                    path.join(directory, entry.name),
                    relativePath,
                )),
            );
        } else if (entry.isFile() && /\.mdx?$/.test(entry.name)) {
            pages.push(relativePath);
        }
    }

    return pages.sort();
};

const normalize = (value) => value.replace(/^\uFEFF/, "").replace(/\r\n?/g, "\n");

const scalar = (value) => {
    const trimmed = value.trim();

    if (
        (trimmed.startsWith('"') && trimmed.endsWith('"')) ||
        (trimmed.startsWith("'") && trimmed.endsWith("'"))
    ) {
        return trimmed.slice(1, -1);
    }

    return trimmed;
};

const plainText = (content) =>
    content
        .replace(/\{\/\*[\s\S]*?\*\/\}/g, " ")
        .replace(/!\[([^\]]*)\]\([^)]+\)/g, "$1")
        .replace(/\[([^\]]+)]\([^)]+\)/g, "$1")
        .replace(/<[^>]+>/g, " ")
        .replace(/[`*~#>|]/g, " ")
        .replace(/:::[A-Za-z-]*/g, " ")
        .replace(/\s+/g, " ")
        .trim();

const parsePage = (source, content) => {
    const normalized = normalize(content);
    const match = normalized.match(/^---\n([\s\S]*?)\n---(?:\n|$)/);

    if (!match) {
        throw new Error(`${source}: front matter is missing.`);
    }

    const frontMatter = match[1];
    const metadata = Object.fromEntries(
        [...frontMatter.matchAll(/^([A-Za-z0-9_-]+):\s*(.+)$/gm)].map(
            ([, key, value]) => [key, scalar(value)],
        ),
    );
    const missing = requiredFields.filter((field) => !metadata[field]);

    if (missing.length > 0) {
        throw new Error(`${source}: missing front matter: ${missing.join(", ")}.`);
    }

    if (!allowedTypes.has(metadata.type)) {
        throw new Error(`${source}: unsupported type ${metadata.type}.`);
    }

    if (!allowedStatuses.has(metadata.status)) {
        throw new Error(`${source}: unsupported status ${metadata.status}.`);
    }

    let keywords;

    try {
        keywords = JSON.parse(metadata.keywords);
    } catch {
        throw new Error(`${source}: keywords must be a JSON-style string array.`);
    }

    if (
        !Array.isArray(keywords) ||
        keywords.length === 0 ||
        keywords.some((keyword) => typeof keyword !== "string" || !keyword)
    ) {
        throw new Error(`${source}: keywords must contain non-empty strings.`);
    }

    const body = normalized
        .slice(match[0].length)
        .replace(/^```[^\n]*\n[\s\S]*?^```\s*$/gm, "");
    const headings = [...body.matchAll(/^(#{1,6})\s+(.+)$/gm)].map(
        ([, marks, rawHeading]) => {
            const explicitId = rawHeading.match(
                /\s+\{\/\*\s*#([^*\s]+)\s*\*\/\}\s*$/,
            )?.[1];
            const text = rawHeading
                .replace(/\s+\{\/\*\s*#[^*]+\*\/\}\s*$/, "")
                .replace(/[`*_]/g, "")
                .trim();
            const id =
                explicitId ??
                text
                    .toLocaleLowerCase("en-US")
                    .replace(/<[^>]+>/g, "")
                    .replace(/[^\p{L}\p{N}\s-]/gu, "")
                    .trim()
                    .replace(/\s+/g, "-");

            return { level: marks.length, id, text };
        },
    );

    return {
        metadata: { ...metadata, keywords },
        headings,
        content: plainText(body),
    };
};

const routeFor = (source, slug) => {
    const value = slug ?? source.replace(/\.mdx?$/, "");
    const withLeadingSlash = value.startsWith("/") ? value : `/${value}`;

    return withLeadingSlash === "/"
        ? withLeadingSlash
        : `${withLeadingSlash.replace(/\/+$/, "")}/`;
};

const searchEntry = (page, route) => ({
    route,
    title: page.title,
    description: page.description,
    keywords: page.keywords,
    headings: page.headings.map((heading) => heading.text),
    content: page.content,
});

const localizedMessage = (catalog, locale, id) => {
    const message = catalog[id]?.message;

    if (typeof message !== "string" || !message.trim()) {
        throw new Error(`${locale}: missing translation ${id}.`);
    }

    return message;
};

const config = normalize(await readFile(configPath, "utf8"));
const defaultLocale = config.match(/defaultLocale:\s*["']([^"']+)["']/)?.[1];
const localeBlock = config.match(/locales:\s*\[([^\]]+)]/)?.[1];
const locales = [...(localeBlock?.matchAll(/["']([^"']+)["']/g) ?? [])].map(
    (match) => match[1],
);

if (!defaultLocale || locales.length === 0 || !locales.includes(defaultLocale)) {
    throw new Error("Unable to read locale configuration.");
}

const redirects = [...config.matchAll(/\{\s*from:\s*"([^"]+)",\s*to:\s*"([^"]+)"\s*\}/gs)].map(
    ([, from, to]) => ({ from, to }),
);
const homepage = {
    route: "/",
    source: "src/pages/index.tsx",
    aliases: redirects
        .filter((redirect) => redirect.to === "/")
        .map((redirect) => redirect.from)
        .sort(),
    title: "Laravel Feeds documentation",
    description:
        "Export large Laravel datasets to XML, JSON, JSON Lines, CSV, RSS, and marketplace feeds.",
    type: "guide",
    status: "stable",
    since: "1.0",
    keywords: ["Laravel", "feeds", "data export", "documentation"],
    content: "",
    headings: [
        {
            level: 1,
            id: "",
            text: "Export large datasets without loading them all into memory",
        },
        {
            level: 2,
            id: "paths-title",
            text: "Start with a task, then use the reference",
        },
    ],
};
const routes = [homepage];

for (const source of await listPages(docsDirectory)) {
    const parsed = parsePage(
        source,
        await readFile(path.join(docsDirectory, source), "utf8"),
    );
    const route = routeFor(source, parsed.metadata.slug);

    routes.push({
        route,
        source,
        aliases: redirects
            .filter((redirect) => redirect.to === route)
            .map((redirect) => redirect.from)
            .sort(),
        title: parsed.metadata.title,
        description: parsed.metadata.description,
        type: parsed.metadata.type,
        status: parsed.metadata.status,
        since: parsed.metadata.since,
        keywords: parsed.metadata.keywords,
        headings: parsed.headings,
        content: parsed.content,
    });
}

routes.sort((left, right) => left.route.localeCompare(right.route, "en"));

const routePaths = new Set();

for (const route of routes) {
    if (routePaths.has(route.route)) {
        throw new Error(`Duplicate route: ${route.route}`);
    }

    routePaths.add(route.route);
}

const redirectSources = new Set();

for (const redirect of redirects) {
    if (redirectSources.has(redirect.from)) {
        throw new Error(`Duplicate redirect source: ${redirect.from}`);
    }

    redirectSources.add(redirect.from);
}

for (const redirect of redirects) {
    if (redirectSources.has(redirect.to)) {
        throw new Error(`Redirect chain detected: ${redirect.from} -> ${redirect.to}`);
    }

    if (!routePaths.has(redirect.to)) {
        throw new Error(`Redirect target is not a documented route: ${redirect.to}`);
    }
}

const search = {
    [defaultLocale]: routes.map((route) => searchEntry(route, route.route)),
};

for (const locale of locales.filter((locale) => locale !== defaultLocale)) {
    const catalog = JSON.parse(
        await readFile(path.join(siteDirectory, "i18n", locale, "code.json"), "utf8"),
    );
    const localizedDirectory = path.join(
        siteDirectory,
        "i18n",
        locale,
        "docusaurus-plugin-content-docs",
        "current",
    );
    const localizedRoutes = [
        {
            route: "/",
            title: localizedMessage(catalog, locale, "homepage.meta.title"),
            description: localizedMessage(
                catalog,
                locale,
                "homepage.meta.description",
            ),
            keywords: homepage.keywords,
            content: "",
            headings: [
                {
                    ...homepage.headings[0],
                    text: localizedMessage(catalog, locale, "homepage.title"),
                },
                {
                    ...homepage.headings[1],
                    text: localizedMessage(catalog, locale, "homepage.paths.title"),
                },
            ],
        },
    ];

    for (const source of await listPages(localizedDirectory)) {
        const parsed = parsePage(
            `${locale}/${source}`,
            await readFile(path.join(localizedDirectory, source), "utf8"),
        );

        localizedRoutes.push({
            route: routeFor(source, parsed.metadata.slug),
            title: parsed.metadata.title,
            description: parsed.metadata.description,
            keywords: parsed.metadata.keywords,
            headings: parsed.headings,
            content: parsed.content,
        });
    }

    localizedRoutes.sort((left, right) =>
        left.route.localeCompare(right.route, "en"),
    );

    if (
        localizedRoutes.length !== routes.length ||
        localizedRoutes.some((route, index) => route.route !== routes[index].route)
    ) {
        throw new Error(`${locale}: localized routes do not match English routes.`);
    }

    search[locale] = localizedRoutes.map((route) =>
        searchEntry(route, `/${locale}${route.route}`),
    );
}

const manifestRoutes = routes.map((route) => {
    const manifestRoute = { ...route };

    delete manifestRoute.content;

    return manifestRoute;
});
const output = `${JSON.stringify({ version: 3, routes: manifestRoutes }, null, 4)}\n`;
const searchOutputs = Object.fromEntries(
    locales.map((locale) => [
        locale,
        `${JSON.stringify(search[locale], null, 4)}\n`,
    ]),
);

if (process.argv.includes("--check")) {
    const current = normalize(await readFile(manifestPath, "utf8"));

    if (current !== output) {
        throw new Error("routes.json is stale. Run npm run content:manifest.");
    }

    for (const locale of locales) {
        const currentSearch = normalize(
            await readFile(path.join(searchDirectory, `${locale}.json`), "utf8"),
        );

        if (currentSearch !== searchOutputs[locale]) {
            throw new Error(
                `static/search/${locale}.json is stale. Run npm run content:manifest.`,
            );
        }
    }
} else {
    await mkdir(searchDirectory, { recursive: true });
    await Promise.all([
        writeFile(manifestPath, output, "utf8"),
        ...locales.map((locale) =>
            writeFile(
                path.join(searchDirectory, `${locale}.json`),
                searchOutputs[locale],
                "utf8",
            ),
        ),
    ]);
}
