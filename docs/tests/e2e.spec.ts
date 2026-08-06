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

test("search closes with Escape", async ({ page }) => {
    await page.goto("/");
    await page.getByRole("button", { name: "Search" }).click();

    const dialog = page.getByRole("dialog", { name: "Search documentation" });
    await expect(dialog).toBeVisible();
    await page.keyboard.press("Escape");
    await expect(dialog).toBeHidden();
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

test("mobile navigation exposes documentation and search", async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto("/");
    await page.locator(".navbar__toggle").click();

    const sidebar = page.locator(".navbar-sidebar");
    await expect(sidebar).toBeVisible();
    await expect(sidebar.getByRole("link", { name: "Documentation" })).toBeVisible();
    await expect(sidebar.getByRole("button", { name: "Search" })).toBeVisible();
});

test("localized routes render translated content", async ({ page }) => {
    await page.goto("/ru/introduction/");

    await expect(page.locator("html")).toHaveAttribute("lang", "ru-RU");
    await expect(
        page.getByRole("heading", { level: 1, name: "Начало работы" }),
    ).toBeVisible();
});
