import type { SidebarsConfig } from "@docusaurus/plugin-content-docs";

const sidebars: SidebarsConfig = {
    docs: [
        {
            type: "category",
            label: "Quick Start",
            link: {
                type: "doc",
                id: "introduction",
            },
            items: [
                "installation",
                "create-feeds",
                "performance",
            ],
        },
        {
            type: "category",
            label: "Guides",
            items: [
                "generation",
                "feed-targets",
                "eloquent",
                "location",
                "events",
                "extending-functionality",
            ],
        },
        {
            type: "category",
            label: "Formats & Recipes",
            items: [
                "supported-formats",
                "presets",
                "receipt-target-feeds",
                "receipt-sitemap",
                "receipt-instagram",
                "receipt-yandex",
                "receipt-rss-atom",
            ],
        },
        {
            type: "category",
            label: "API Reference",
            link: {
                type: "doc",
                id: "api/index",
            },
            items: [
                "api/configuration",
                "api/feed",
                "api/runtime",
                "elements",
                "directives",
                "api/events-exceptions",
            ],
        },
        {
            type: "category",
            label: "Troubleshooting",
            link: {
                type: "doc",
                id: "faq",
            },
            items: ["troubleshooting"],
        },
        {
            type: "category",
            label: "Project",
            items: [
                "upgrade-guide",
                "compatibility",
                "contributions",
                "machine-learning",
                "license",
            ],
        },
    ],
};

export default sidebars;
