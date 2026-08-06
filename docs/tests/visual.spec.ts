import { expect, test, type Page } from "@playwright/test";

const prepare = async (
    page: Page,
    path: string,
    colorScheme: "dark" | "light" = "light",
) => {
    await page.emulateMedia({ colorScheme, reducedMotion: "reduce" });
    await page.goto(path);
    await page.evaluate(() => document.fonts.ready);
};

test("homepage light desktop", async ({ page }) => {
    await prepare(page, "/");
    await expect(page).toHaveScreenshot("homepage-light-desktop.png", {
        fullPage: true,
    });
});

test("homepage dark desktop", async ({ page }) => {
    await prepare(page, "/", "dark");
    await expect(page).toHaveScreenshot("homepage-dark-desktop.png", {
        fullPage: true,
    });
});

test("guide light desktop", async ({ page }) => {
    await prepare(page, "/generation/");
    await expect(page).toHaveScreenshot("generation-light-desktop.png", {
        fullPage: true,
    });
});

test("API light mobile", async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await prepare(page, "/api/");
    await expect(page).toHaveScreenshot("api-light-mobile.png", {
        fullPage: true,
    });
});

test("expanded FAQ state", async ({ page }) => {
    await prepare(page, "/faq/");
    await page.locator("details").first().locator("summary").click();
    await expect(page.locator("details").first()).toHaveScreenshot(
        "faq-expanded.png",
    );
});
