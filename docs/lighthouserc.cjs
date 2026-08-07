const { chromium } = require("playwright");
const { existsSync } = require("node:fs");

const systemChrome = "C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe";
const chromePath =
    process.env.CHROME_PATH ??
    process.env.PLAYWRIGHT_CHROMIUM_EXECUTABLE_PATH ??
    (existsSync(systemChrome) ? systemChrome : chromium.executablePath());

module.exports = {
    ci: {
        collect: {
            chromePath,
            numberOfRuns: 3,
            staticDistDir: "./build",
            url: [
                "http://localhost/",
                "http://localhost/generation/",
            ],
            settings: {
                chromeFlags:
                    typeof process.getuid === "function" && process.getuid() === 0
                        ? "--no-sandbox"
                        : undefined,
                throttling: {
                    cpuSlowdownMultiplier: 3,
                    rttMs: 100,
                    throughputKbps: 2048,
                },
                onlyCategories: [
                    "performance",
                    "accessibility",
                    "best-practices",
                    "seo",
                ],
            },
        },
        assert: {
            assertions: {
                "categories:performance": [
                    "error",
                    { aggregationMethod: "median", minScore: 0.9 },
                ],
                "categories:accessibility": [
                    "error",
                    { aggregationMethod: "pessimistic", minScore: 1 },
                ],
                "categories:best-practices": [
                    "error",
                    { aggregationMethod: "pessimistic", minScore: 0.95 },
                ],
                "categories:seo": [
                    "error",
                    { aggregationMethod: "pessimistic", minScore: 1 },
                ],
                "cumulative-layout-shift": [
                    "error",
                    {
                        aggregationMethod: "pessimistic",
                        maxNumericValue: 0.05,
                    },
                ],
                "largest-contentful-paint": [
                    "error",
                    { aggregationMethod: "median", maxNumericValue: 2000 },
                ],
                "resource-summary:script:size": [
                    "error",
                    { maxNumericValue: 450000 },
                ],
                "resource-summary:stylesheet:size": [
                    "error",
                    { maxNumericValue: 120000 },
                ],
            },
        },
        upload: {
            outputDir: "./lighthouse-reports",
            target: "filesystem",
        },
    },
};
