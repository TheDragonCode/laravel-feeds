import AxeBuilder from "@axe-core/playwright";
import { expect, test } from "@playwright/test";

type AccessibilityCase = {
    colorScheme?: "dark" | "light";
    name: string;
    path: string;
    viewport?: { height: number; width: number };
};

const cases: AccessibilityCase[] = [
    { name: "homepage desktop light", path: "/" },
    { name: "guide desktop light", path: "/generation/" },
    { name: "API desktop light", path: "/api/" },
    { colorScheme: "dark", name: "homepage desktop dark", path: "/" },
    { colorScheme: "dark", name: "guide desktop dark", path: "/generation/" },
    { colorScheme: "dark", name: "API desktop dark", path: "/api/" },
    {
        name: "homepage mobile light",
        path: "/",
        viewport: { height: 844, width: 390 },
    },
    {
        name: "guide mobile light",
        path: "/generation/",
        viewport: { height: 844, width: 390 },
    },
    {
        name: "API mobile light",
        path: "/api/",
        viewport: { height: 844, width: 390 },
    },
    { name: "Russian homepage", path: "/ru/" },
    { name: "German guide", path: "/de/generation/" },
    { name: "Korean API", path: "/ko/api/" },
    {
        name: "Chinese FAQ mobile",
        path: "/zh-CN/faq/",
        viewport: { height: 844, width: 390 },
    },
];

for (const accessibilityCase of cases) {
    test(`a11y: ${accessibilityCase.name}`, async ({ page }) => {
        if (accessibilityCase.viewport) {
            await page.setViewportSize(accessibilityCase.viewport);
        }

        await page.emulateMedia({
            colorScheme: accessibilityCase.colorScheme ?? "light",
            reducedMotion: "reduce",
        });
        await page.goto(accessibilityCase.path);
        await page.evaluate(() => document.fonts.ready);

        const results = await new AxeBuilder({ page })
            .withTags(["wcag2a", "wcag2aa", "wcag21aa", "wcag22aa"])
            .analyze();
        const blockingViolations = results.violations.filter(
            (violation) =>
                violation.impact === "critical" || violation.impact === "serious",
        );

        expect(
            blockingViolations.map((violation) => ({
                id: violation.id,
                impact: violation.impact,
                targets: violation.nodes.map((node) => node.target.join(" ")),
            })),
        ).toEqual([]);
    });
}
