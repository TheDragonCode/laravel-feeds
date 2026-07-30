import type { SidebarsConfig } from "@docusaurus/plugin-content-docs";

const sidebars: SidebarsConfig = {
    docs: [
        {
            type: "category",
            label: "Getting Started",
            link: {
                type: "doc",
                id: "introduction",
            },
            items: ["installation", "create-feeds", "generation"],
        },
        {
            type: "category",
            label: "Digging Deeper",
            items: [
                "supported-formats",
                "elements",
                "directives",
                "eloquent",
                "location",
                "presets",
                "events",
                "extending-functionality",
            ],
        },
        {
            type: "category",
            label: "Recipes",
            items: [
                "receipt-sitemap",
                "receipt-instagram",
                "receipt-yandex",
                "receipt-rss-atom",
            ],
        },
        {
            type: "category",
            label: "Project",
            items: ["contributions", "machine-learning", "license"],
        },
    ],
};

export default sidebars;
