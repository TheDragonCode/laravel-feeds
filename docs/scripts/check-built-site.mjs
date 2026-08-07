import { readFile } from "node:fs/promises";
import path from "node:path";
import { fileURLToPath } from "node:url";

const siteDirectory = path.resolve(
    path.dirname(fileURLToPath(import.meta.url)),
    "..",
);
const buildDirectory = path.join(siteDirectory, "build");
const config = await readFile(
    path.join(siteDirectory, "docusaurus.config.ts"),
    "utf8",
);
const manifest = JSON.parse(
    await readFile(path.join(siteDirectory, "routes.json"), "utf8"),
);
const siteUrl = config.match(/const siteUrl = "([^"]+)"/)?.[1];
const defaultLocale = config.match(/defaultLocale:\s*"([^"]+)"/)?.[1];
const localeBlock = config.match(/locales:\s*\[([^\]]+)]/)?.[1];
const locales = [...(localeBlock?.matchAll(/"([^"]+)"/g) ?? [])].map(
    (match) => match[1],
);
const htmlLangs = [...config.matchAll(/htmlLang:\s*"([^"]+)"/g)].map(
    (match) => match[1],
);

if (
    !siteUrl ||
    !defaultLocale ||
    locales.length === 0 ||
    locales.length !== htmlLangs.length
) {
    throw new Error("Unable to read site URL and locale configuration.");
}

const attribute = (tag, name) => {
    const match = tag.match(
        new RegExp(
            `\\b${name}\\s*=\\s*(?:"([^"]*)"|'([^']*)'|([^\\s>]+))`,
            "i",
        ),
    );

    return match?.[1] ?? match?.[2] ?? match?.[3];
};

const outputPath = (locale, route) => {
    const prefix = locale === defaultLocale ? "" : `/${locale}`;
    const relative = `${prefix}${route}`.replace(/^\/+|\/+$/g, "");

    return relative
        ? path.join(buildDirectory, relative, "index.html")
        : path.join(buildDirectory, "index.html");
};

const expectedCanonical = (locale, route) => {
    const prefix = locale === defaultLocale ? "" : `/${locale}`;

    return `${siteUrl}${prefix}${route}`;
};

const expectedHreflangs = new Set([...htmlLangs, "x-default"]);
const errors = [];

for (const [localeIndex, locale] of locales.entries()) {
    for (const route of manifest.routes) {
        const filename = outputPath(locale, route.route);
        let html;

        try {
            html = await readFile(filename, "utf8");
        } catch (error) {
            if (error.code === "ENOENT") {
                errors.push(`${locale}${route.route}: built page is missing`);
                continue;
            }

            throw error;
        }

        const htmlTag = html.match(/<html\b[^>]*>/i)?.[0];
        const actualLanguage = htmlTag ? attribute(htmlTag, "lang") : undefined;

        if (actualLanguage !== htmlLangs[localeIndex]) {
            errors.push(
                `${locale}${route.route}: expected html lang ${htmlLangs[localeIndex]}`,
            );
        }

        const title = html.match(/<title\b[^>]*>([\s\S]*?)<\/title>/i)?.[1].trim();

        if (!title) {
            errors.push(`${locale}${route.route}: title is missing`);
        }

        const h1Count = html.match(/<h1\b/gi)?.length ?? 0;

        if (h1Count !== 1) {
            errors.push(`${locale}${route.route}: expected one H1, found ${h1Count}`);
        }

        const metaTags = html.match(/<meta\b[^>]*>/gi) ?? [];
        const descriptionTag = metaTags.find(
            (tag) => attribute(tag, "name")?.toLowerCase() === "description",
        );
        const description = descriptionTag
            ? attribute(descriptionTag, "content")
            : undefined;

        if (!description || description.trim().length < 8) {
            errors.push(`${locale}${route.route}: description is missing or too short`);
        }

        const linkTags = html.match(/<link\b[^>]*>/gi) ?? [];
        const canonicalTags = linkTags.filter(
            (tag) => attribute(tag, "rel")?.toLowerCase() === "canonical",
        );
        const canonical = canonicalTags[0]
            ? attribute(canonicalTags[0], "href")
            : undefined;
        const expected = expectedCanonical(locale, route.route);

        if (canonicalTags.length !== 1 || canonical !== expected) {
            errors.push(
                `${locale}${route.route}: expected canonical ${expected}, found ${canonical ?? "none"}`,
            );
        }

        const actualHreflangs = new Set(
            linkTags
                .filter(
                    (tag) => attribute(tag, "rel")?.toLowerCase() === "alternate",
                )
                .map((tag) => attribute(tag, "hreflang"))
                .filter(Boolean),
        );

        if (
            actualHreflangs.size !== expectedHreflangs.size ||
            [...expectedHreflangs].some((value) => !actualHreflangs.has(value))
        ) {
            errors.push(`${locale}${route.route}: hreflang set is incomplete`);
        }

        const structuredData = html.match(
            /<script\b[^>]*type=(?:"application\/ld\+json"|'application\/ld\+json'|application\/ld\+json)[^>]*>([\s\S]*?)<\/script>/i,
        )?.[1];

        if (!structuredData) {
            errors.push(`${locale}${route.route}: JSON-LD is missing`);
        } else {
            try {
                JSON.parse(structuredData);
            } catch {
                errors.push(`${locale}${route.route}: JSON-LD is invalid`);
            }
        }
    }
}

const redirects = manifest.routes.flatMap((route) =>
    route.aliases.map((alias) => ({ from: alias, to: route.route })),
);
const redirectSources = new Set(redirects.map((redirect) => redirect.from));

for (const redirect of redirects) {
    if (redirectSources.has(redirect.to)) {
        errors.push(`${redirect.from}: redirect chain points to ${redirect.to}`);
    }

    const relative = redirect.from.replace(/^\//, "");
    const filename = path.join(buildDirectory, relative, "index.html");

    try {
        const html = await readFile(filename, "utf8");

        if (!html.includes(redirect.to)) {
            errors.push(`${redirect.from}: redirect target ${redirect.to} is missing`);
        }
    } catch (error) {
        if (error.code === "ENOENT") {
            errors.push(`${redirect.from}: redirect page is missing`);
        } else {
            throw error;
        }
    }
}

if (errors.length > 0) {
    throw new Error(
        `Built-site validation failed:\n${errors.map((error) => `- ${error}`).join("\n")}`,
    );
}

console.log(
    `Validated metadata and SEO for ${manifest.routes.length} routes across ${locales.length} locales, plus ${redirects.length} redirects.`,
);
