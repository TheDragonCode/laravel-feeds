import { expect, test } from "@playwright/test";

test("homepage leads to installation", async ({ page }) => {
    await page.goto("/");

    await expect(
        page.getByRole("heading", {
            level: 1,
            name: "Export large datasets without loading them all into memory",
        }),
    ).toBeVisible();

    await page.getByRole("link", { name: "Start in 5 minutes" }).click();

    await expect(page).toHaveURL(/\/installation\/$/);
    await expect(
        page.getByRole("heading", { level: 1, name: "Installation & setup" }),
    ).toBeVisible();
});

test("legacy introduction URL redirects to getting started", async ({ page }) => {
    await page.goto("/introduction.html");

    await expect(page).toHaveURL(/\/introduction\/$/);
    await expect(
        page.getByRole("heading", { level: 1, name: "Getting started" }),
    ).toBeVisible();
});

for (const locale of ["", "be/", "de/", "fr/", "ko/", "pt-BR/", "ru/", "uk/", "zh-CN/"]) {
    for (const [anchor, destination, heading] of [
        ["list_feeds", "list-feeds/", "h1"],
        ["list_feeds_agent", "list-feeds/#list_feeds_agent", "#list_feeds_agent"],
    ]) {
        test(`legacy listing link ${locale}generation/#${anchor} opens the moved guide`, async ({ page }) => {
            await page.goto(`/${locale}generation/#${anchor}`);

            const link = page.getByRole("link").filter({ has: page.locator(`#${anchor}`) });
            const target = `/${locale}${destination}`;

            await expect(link).toHaveAttribute("href", target);
            await link.click();
            await expect(page).toHaveURL(new URL(target, page.url()).href);
            await expect(page.locator(heading)).toBeVisible();
        });
    }
}

test("search opens from the keyboard and navigates", async ({ page }) => {
    await page.goto("/");
    await expect(page.locator('[data-search-ready="true"]')).toBeVisible();
    await page.keyboard.press("Control+k");

    const dialog = page.getByRole("dialog", { name: "Search documentation" });
    await expect(dialog).toBeVisible();

    await dialog.getByRole("searchbox").fill("runtime services");
    await dialog.getByRole("link", { name: /Runtime services and contracts/ }).click();

    await expect(page).toHaveURL(/\/api\/runtime\/$/);
    await expect(
        page.getByRole("heading", {
            level: 1,
            name: "Runtime services and contracts",
        }),
    ).toBeVisible();
});

test("search indexes page bodies", async ({ page }) => {
    await page.goto("/");
    await page.getByRole("button", { name: "Search" }).click();

    const dialog = page.getByRole("dialog", { name: "Search documentation" });
    const searchbox = dialog.getByRole("searchbox");

    await searchbox.fill("deleteByClass");
    await expect(
        dialog.getByRole("link", { name: /Runtime services and contracts/ }),
    ).toBeVisible();

    await searchbox.fill("FEED_QUEUE_NAME");
    await expect(
        dialog.getByRole("link", { name: /Configuration reference/ }),
    ).toBeVisible();
});

test("search closes with Escape", async ({ page }) => {
    await page.goto("/");
    await page.getByRole("button", { name: "Search" }).click();

    const dialog = page.getByRole("dialog", { name: "Search documentation" });
    await expect(dialog).toBeVisible();
    await page.keyboard.press("Escape");
    await expect(dialog).toBeHidden();
});

test("search retries the locale index after a failed request", async ({ page }) => {
    let attempts = 0;

    await page.route("**/search/en.json", async (route) => {
        attempts++;

        if (attempts === 1) {
            await route.fulfill({ status: 503 });

            return;
        }

        await route.continue();
    });

    await page.goto("/");

    const trigger = page.getByRole("button", { name: "Search" });
    const dialog = page.getByRole("dialog", { name: "Search documentation" });
    const firstResponse = page.waitForResponse(
        (response) => new URL(response.url()).pathname === "/search/en.json",
    );

    await trigger.click();
    await firstResponse;
    await dialog.getByRole("button", { name: "Close" }).click();
    await trigger.click();
    await dialog.getByRole("searchbox").fill("runtime services");

    await expect(
        dialog.getByRole("link", { name: /Runtime services and contracts/ }),
    ).toBeVisible();
    expect(attempts).toBe(2);
});

test("installation command copies to the clipboard", async ({ context, page }) => {
    await context.grantPermissions(["clipboard-read", "clipboard-write"]);
    await page.goto("/");

    await page.getByRole("button", { name: "Copy" }).click();

    await expect(page.getByRole("button", { name: "Copied" })).toBeVisible();
    await expect
        .poll(() => page.evaluate(() => navigator.clipboard.readText()))
        .toBe("composer require dragon-code/laravel-feeds");
});

test("sidebar navigation opens another guide", async ({ page }) => {
    await page.goto("/generation/");

    const sidebar = page.locator(".theme-doc-sidebar-container");
    await sidebar.getByRole("link", { name: "Events", exact: true }).click();

    await expect(page).toHaveURL(/\/events\/$/);
    await expect(page.getByRole("heading", { level: 1, name: "Events" })).toBeVisible();
});

test("mobile search closes navigation after selection", async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto("/");
    await page.locator(".navbar__toggle").click();

    const sidebar = page.locator(".navbar-sidebar");
    const search = sidebar.getByRole("button", { name: "Search" });

    await expect(sidebar).toBeVisible();
    await expect(sidebar.getByRole("link", { name: "Documentation" })).toBeVisible();
    await expect(search.locator("..")).toHaveClass(/menu__list-item/);

    await search.click();
    const dialog = page.getByRole("dialog", { name: "Search documentation" });
    await dialog.getByRole("searchbox").fill("runtime services");
    await dialog.getByRole("link", { name: /Runtime services and contracts/ }).click();

    await expect(page).toHaveURL(/\/api\/runtime\/$/);
    await expect(sidebar).toBeHidden();
    await expect(page.locator("body")).toHaveCSS("overflow", "visible");
});

test("localized routes render translated content", async ({ page }) => {
    await page.goto("/ru/introduction/");

    await expect(page.locator("html")).toHaveAttribute("lang", "ru-RU");
    await expect(
        page.getByRole("heading", { level: 1, name: "Начало работы" }),
    ).toBeVisible();
});

for (const locale of ["", "ru/"]) {
    test(`version selection preserves the compatibility page for ${locale || "en"}`, async ({ page }) => {
        await page.goto(`/${locale}compatibility/`);

        const navbar = page.locator(".navbar");
        const phpVersion = page.getByRole("row")
            .filter({ has: page.getByRole("cell", { name: "PHP", exact: true }) })
            .locator("code")
            .first();

        await expect(phpVersion).toHaveText("8.4");
        await navbar.getByRole("button", { name: "2.x", exact: true }).click();
        await navbar.getByRole("link", { name: "1.x", exact: true }).click();

        await expect(page).toHaveURL(`/${locale}1.x/compatibility/`);
        await expect(phpVersion).toHaveText("8.2");

        await navbar.getByRole("button", { name: "1.x", exact: true }).click();
        await navbar.getByRole("link", { name: "2.x", exact: true }).click();

        await expect(page).toHaveURL(`/${locale}compatibility/`);
        await expect(phpVersion).toHaveText("8.4");
    });
}

test("localized search uses translated content", async ({ page }) => {
    const searchAssets = new Set<string>();

    page.on("response", (response) => {
        const pathname = new URL(response.url()).pathname;

        if (pathname.startsWith("/search/") && pathname.endsWith(".json")) {
            searchAssets.add(pathname);
        }
    });

    await page.goto("/ru/");
    await page.getByRole("button", { name: "Поиск" }).click();

    const dialog = page.getByRole("dialog", { name: "Поиск по документации" });
    await dialog.getByRole("searchbox").fill("установка");
    await dialog.getByRole("link", { name: /Установка и настройка/ }).click();

    await expect(page).toHaveURL(/\/ru\/installation\/$/);
    await expect(
        page.getByRole("heading", { level: 1, name: "Установка и настройка" }),
    ).toBeVisible();
    expect([...searchAssets]).toEqual(["/search/ru.json"]);
});

for (const locale of ["en", "ru"]) {
    const prefix = locale === "en" ? "" : `/${locale}`;
    const triggerName = locale === "en" ? "Search" : "Поиск";
    const compatibility = locale === "en" ? /Compatibility/ : /Совместимость/;

    test(`archived search uses ${locale} version content and routes`, async ({ page }) => {
        const searchAssets = new Set<string>();

        page.on("request", (request) => {
            const pathname = new URL(request.url()).pathname;

            if (pathname.startsWith("/search/") && pathname.endsWith(".json")) {
                searchAssets.add(pathname);
            }
        });

        await page.goto(`${prefix}/1.x/compatibility/`);
        await page.getByRole("button", { name: triggerName }).click();

        const dialog = page.getByRole("dialog");
        await dialog.getByRole("searchbox").fill("8.2");

        const result = dialog.getByRole("link", { name: compatibility });
        await expect(result).toHaveAttribute("href", `${prefix}/1.x/compatibility/`);
        expect([...searchAssets]).toEqual([`/search/1.x/${locale}.json`]);
        await result.click();

        await expect(page).toHaveURL(`${prefix}/1.x/compatibility/`);
    });

    test(`search follows ${locale} version switches without stale results`, async ({ page }) => {
        await page.goto(`${prefix}/compatibility/`);

        const trigger = page.getByRole("button", { name: triggerName });
        const dialog = page.getByRole("dialog");
        const result = dialog.getByRole("link", { name: compatibility });
        const navbar = page.locator(".navbar");

        await trigger.click();
        await dialog.getByRole("searchbox").fill("8.4");
        await expect(result).toHaveAttribute("href", `${prefix}/compatibility/`);
        await dialog.getByRole("button").click();
        await expect(dialog).toBeHidden();

        for (const [from, to, php, route] of [
            ["2.x", "1.x", "8.2", `${prefix}/1.x/compatibility/`],
            ["1.x", "2.x", "8.4", `${prefix}/compatibility/`],
            ["2.x", "1.x", "8.2", `${prefix}/1.x/compatibility/`],
        ]) {
            await navbar.getByRole("button", { name: from, exact: true }).click();
            await navbar.getByRole("link", { name: to, exact: true }).click();

            await trigger.click();
            await dialog.getByRole("searchbox").fill(php);
            await expect(result).toHaveAttribute("href", route);
            await dialog.getByRole("button").click();
            await expect(dialog).toBeHidden();
        }

        await navbar.locator(".navbar__brand").click();
        await expect(page).toHaveURL(`${prefix}/`);
        await trigger.click();
        await dialog.getByRole("searchbox").fill("8.4");
        await expect(result).toHaveAttribute("href", `${prefix}/compatibility/`);
    });
}

test("search ignores a delayed index after switching versions", async ({ page }) => {
    let currentRequests = 0;
    let releaseArchive: () => void = () => undefined;
    const archivedRequest = new Promise<void>((resolve) => {
        releaseArchive = resolve;
    });

    page.on("close", releaseArchive);
    page.on("request", (request) => {
        if (new URL(request.url()).pathname === "/search/en.json") {
            currentRequests++;
        }
    });
    await page.route("**/search/1.x/en.json", async (route) => {
        const response = await route.fetch();
        await archivedRequest;
        await route.fulfill({ response });
    });
    await page.goto("/1.x/compatibility/");

    const trigger = page.getByRole("button", { name: "Search" });
    const dialog = page.getByRole("dialog");
    const archiveStarted = page.waitForRequest("**/search/1.x/en.json");
    const archiveFinished = page.waitForResponse("**/search/1.x/en.json");

    await trigger.click();
    await archiveStarted;
    await dialog.getByRole("button").click();
    await expect(dialog).toBeHidden();

    const navbar = page.locator(".navbar");
    await navbar.getByRole("button", { name: "1.x", exact: true }).click();
    await navbar.getByRole("link", { name: "2.x", exact: true }).click();
    await trigger.click();
    await dialog.getByRole("searchbox").fill("8.4");
    await expect(dialog.getByRole("link", { name: /Compatibility/ }))
        .toHaveAttribute("href", "/compatibility/");

    releaseArchive();
    await (await archiveFinished).finished();
    await dialog.getByRole("searchbox").fill("8.2");
    await expect(dialog.getByRole("link", { name: /Compatibility/ })).toHaveCount(0);
    await dialog.getByRole("searchbox").fill("8.4");
    await expect(dialog.getByRole("link", { name: /Compatibility/ }))
        .toHaveAttribute("href", "/compatibility/");
    expect(currentRequests).toBe(1);
});

test("search does not treat a version-like unknown route as archived", async ({ page }) => {
    await page.goto("/1.x-unknown/");
    await page.getByRole("button", { name: "Search" }).click();

    const dialog = page.getByRole("dialog");
    await dialog.getByRole("searchbox").fill("8.4");
    await expect(dialog.getByRole("link", { name: /Compatibility/ }))
        .toHaveAttribute("href", "/compatibility/");
});

test("localized search finds separate output files by common wording", async ({
    page,
}) => {
    await page.goto("/ru/");
    await page.getByRole("button", { name: "Поиск" }).click();

    const dialog = page.getByRole("dialog", { name: "Поиск по документации" });
    const searchbox = dialog.getByRole("searchbox");
    const firstResult = dialog.getByRole("link").first();

    for (const query of ["разбивка на файлы", "разделение на файлы", "файлы"]) {
        await searchbox.fill(query);
        await expect(firstResult).toContainText(
            "Отдельные файлы для партнёров, магазинов и локалей",
        );
    }

    await firstResult.click();

    await expect(page).toHaveURL(/\/ru\/feed-targets\/$/);
});
