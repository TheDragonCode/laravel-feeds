import { defineConfig, devices } from "@playwright/test";
import { existsSync } from "node:fs";

const baseURL = process.env.PLAYWRIGHT_TEST_BASE_URL ?? "http://127.0.0.1:4173";
const systemChrome = "C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe";
const executablePath =
    process.env.PLAYWRIGHT_CHROMIUM_EXECUTABLE_PATH ??
    (existsSync(systemChrome) ? systemChrome : undefined);

export default defineConfig({
    testDir: "./tests",
    outputDir: "./test-results",
    fullyParallel: false,
    forbidOnly: Boolean(process.env.CI),
    retries: process.env.CI ? 1 : 0,
    workers: process.env.CI ? 1 : undefined,
    reporter: [
        ["line"],
        ["html", { open: "never", outputFolder: "playwright-report" }],
    ],
    expect: {
        timeout: 5000,
        toHaveScreenshot: {
            animations: "disabled",
            caret: "hide",
            maxDiffPixelRatio: 0.001,
        },
    },
    use: {
        ...devices["Desktop Chrome"],
        baseURL,
        colorScheme: "light",
        locale: "en-US",
        screenshot: "only-on-failure",
        timezoneId: "UTC",
        trace: "retain-on-failure",
    },
    projects: [
        {
            name: "chromium",
            use: {
                browserName: "chromium",
                ...(executablePath
                    ? { launchOptions: { executablePath } }
                    : {}),
            },
        },
    ],
    webServer: process.env.PLAYWRIGHT_TEST_BASE_URL
        ? undefined
        : {
              command: "npm run serve -- --host 127.0.0.1 --port 4173",
              reuseExistingServer: !process.env.CI,
              timeout: 120000,
              url: baseURL,
          },
});
