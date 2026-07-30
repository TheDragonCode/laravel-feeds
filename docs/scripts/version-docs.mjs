import { access, readFile, rm, writeFile } from "node:fs/promises";
import path from "node:path";
import { spawnSync } from "node:child_process";
import { fileURLToPath } from "node:url";

const siteDirectory = path.resolve(
    path.dirname(fileURLToPath(import.meta.url)),
    "..",
);
const version = process.argv[2];

if (!version || !/^[A-Za-z0-9][A-Za-z0-9._-]*$/.test(version)) {
    throw new Error("Provide a version containing only letters, digits, dots, underscores, and hyphens.");
}

if (version === "current") {
    throw new Error("The version name current is reserved by Docusaurus.");
}

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

const localizedVersions = locales
    .filter((locale) => locale !== defaultLocale)
    .map((locale) => {
        const directory = path.join(
            siteDirectory,
            "i18n",
            locale,
            "docusaurus-plugin-content-docs",
        );

        return {
            locale,
            sourceDirectory: path.join(directory, "current"),
            sourceCatalog: path.join(directory, "current.json"),
            targetDirectory: path.join(directory, `version-${version}`),
            targetCatalog: path.join(directory, `version-${version}.json`),
        };
    });

const assertMissing = async (target) => {
    try {
        await access(target);
    } catch (error) {
        if (error.code === "ENOENT") {
            return;
        }

        throw error;
    }

    throw new Error(`Version target already exists: ${target}`);
};

for (const localizedVersion of localizedVersions) {
    await access(localizedVersion.sourceDirectory);
    await access(localizedVersion.sourceCatalog);
    await assertMissing(localizedVersion.targetDirectory);
    await assertMissing(localizedVersion.targetCatalog);

    const catalog = JSON.parse(
        await readFile(localizedVersion.sourceCatalog, "utf8"),
    );

    if (!catalog["version.label"]) {
        throw new Error(
            `Missing version.label in the ${localizedVersion.locale} catalog.`,
        );
    }
}

await assertMissing(
    path.join(siteDirectory, "versioned_docs", `version-${version}`),
);

const versionsManifestPath = path.join(siteDirectory, "versions.json");
let previousVersionsManifest = null;

try {
    previousVersionsManifest = await readFile(versionsManifestPath, "utf8");
} catch (error) {
    if (error.code !== "ENOENT") {
        throw error;
    }
}

const versionTargets = [
    path.join(siteDirectory, "versioned_docs", `version-${version}`),
    path.join(
        siteDirectory,
        "versioned_sidebars",
        `version-${version}-sidebars.json`,
    ),
    ...localizedVersions.flatMap((localizedVersion) => [
        localizedVersion.targetDirectory,
        localizedVersion.targetCatalog,
    ]),
];

const rollbackVersion = async () => {
    await Promise.all(
        versionTargets.map((target) =>
            rm(target, { recursive: true, force: true }),
        ),
    );

    if (previousVersionsManifest === null) {
        await rm(versionsManifestPath, { force: true });
    } else {
        await writeFile(versionsManifestPath, previousVersionsManifest, "utf8");
    }
};
await assertMissing(
    path.join(
        siteDirectory,
        "versioned_sidebars",
        `version-${version}-sidebars.json`,
    ),
);

const run = (args) => {
    const result = spawnSync(process.execPath, args, {
        cwd: siteDirectory,
        stdio: "inherit",
    });

    if (result.error) {
        throw result.error;
    }

    if (result.status !== 0) {
        throw new Error(`Command failed with exit code ${result.status}.`);
    }
};

run([path.join(siteDirectory, "scripts", "check-i18n.mjs")]);
run([path.join(siteDirectory, "scripts", "check-snippets.mjs")]);
try {
    run([
        path.join(
            siteDirectory,
            "node_modules",
            "@docusaurus",
            "core",
            "bin",
            "docusaurus.mjs",
        ),
        "docs:version",
        version,
    ]);

    for (const localizedVersion of localizedVersions) {
        const catalog = JSON.parse(
            await readFile(localizedVersion.targetCatalog, "utf8"),
        );

        if (!catalog["version.label"]) {
            throw new Error(
                `Missing version.label in the ${localizedVersion.locale} version catalog.`,
            );
        }

        catalog["version.label"].message = version;
        catalog["version.label"].description =
            `${catalog["version.label"].description} (${version})`;

        await writeFile(
            localizedVersion.targetCatalog,
            `${JSON.stringify(catalog, null, 2)}\n`,
            "utf8",
        );
    }

    run([path.join(siteDirectory, "scripts", "check-i18n.mjs")]);
} catch (error) {
    try {
        await rollbackVersion();
    } catch (rollbackError) {
        throw new AggregateError(
            [error, rollbackError],
            `Unable to create or roll back documentation version ${version}.`,
        );
    }

    throw error;
}

console.log(
    `Created documentation version ${version} for ${locales.length} locales.`,
);
