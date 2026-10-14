import { expect, test } from "@playwright/test";

test("archived documentation links to its frozen knowledge base", async ({ page }) => {
    await page.goto("/1.x/machine-learning/");

    await expect(page.getByRole("link", { name: "llms.txt", exact: true }))
        .toHaveAttribute("href", "https://feeds.dragon-code.pro/1.x/llms.txt");
});

test("archived knowledge base preserves requirements and internal links", async ({ request }) => {
    const response = await request.get("/1.x/llms.txt");

    expect(response.ok()).toBeTruthy();

    const content = await response.text();

    expect(content).toContain("Documentation: https://feeds.dragon-code.pro/1.x/introduction/");
    expect(content).toContain("- PHP `8.2`");
    expect(content).toContain("](https://feeds.dragon-code.pro/1.x/compatibility/)");
    expect(content).toContain("](https://feeds.dragon-code.pro/1.x/api/)");
    expect(content).not.toContain("](https://feeds.dragon-code.pro/compatibility/)");
});
