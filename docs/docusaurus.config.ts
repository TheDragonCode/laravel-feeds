import type * as Preset from "@docusaurus/preset-classic";
import type { Config, Plugin } from "@docusaurus/types";
import { themes as prismThemes } from "prism-react-renderer";

const repositoryUrl = "https://github.com/TheDragonCode/laravel-feeds";
const siteUrl = "https://feeds.dragon-code.pro";
const siteDescription =
    "Fast export of large datasets to feeds for marketplaces and services.";
const yandexMetrikaId = 103961626;

const legacyRedirects = [
    { from: "/contributions.html", to: "/contributions/" },
    { from: "/machine-learning.html", to: "/machine-learning/" },
    { from: "/license.html", to: "/license/" },
    { from: "/introduction.html", to: "/introduction/" },
    { from: "/installation.html", to: "/installation/" },
    { from: "/create-feeds.html", to: "/create-feeds/" },
    { from: "/generation.html", to: "/generation/" },
    { from: "/supported-formats.html", to: "/supported-formats/" },
    { from: "/elements.html", to: "/elements/" },
    { from: "/directives.html", to: "/directives/" },
    { from: "/eloquent.html", to: "/eloquent/" },
    { from: "/location.html", to: "/location/" },
    { from: "/presets.html", to: "/presets/" },
    { from: "/events.html", to: "/events/" },
    {
        from: "/extending-functionality.html",
        to: "/extending-functionality/",
    },
    { from: "/receipt-sitemap.html", to: "/receipt-sitemap/" },
    { from: "/receipt-instagram.html", to: "/receipt-instagram/" },
    { from: "/receipt-yandex.html", to: "/receipt-yandex/" },
    { from: "/receipt-rss-atom.html", to: "/receipt-rss-atom/" },
    { from: "/receipt-partner-feeds/", to: "/receipt-target-feeds/" },
];

const structuredData = {
    "@context": "https://schema.org",
    "@type": "SoftwareSourceCode",
    name: "Laravel Feeds",
    description: siteDescription,
    url: siteUrl,
    codeRepository: repositoryUrl,
    license: `${repositoryUrl}/blob/main/LICENSE`,
    programmingLanguage: "PHP",
    runtimePlatform: "Laravel",
    author: {
        "@type": "Organization",
        name: "The Dragon Code",
        url: "https://github.com/TheDragonCode",
    },
};

function yandexMetrikaPlugin(): Plugin {
    return {
        name: "yandex-metrika",
        injectHtmlTags() {
            return {
                headTags: [
                    {
                        tagName: "script",
                        attributes: {
                            type: "text/javascript",
                        },
                        innerHTML: `(function(w,d,t,r,i,k,a){
    var started=false;
    var events=['pointerdown','keydown','touchstart','scroll'];
    function load(){
        if(started){return;}
        started=true;
        for(var j=0;j<events.length;j++){w.removeEventListener(events[j],queue);}
        w[i]=w[i]||function(){(w[i].a=w[i].a||[]).push(arguments)};
        w[i].l=1*new Date();
        k=d.createElement(t),a=d.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a);
        w[i](${yandexMetrikaId},'init',{ssr:true,webvisor:true,clickmap:true,ecommerce:"dataLayer",accurateTrackBounce:true,trackLinks:true});
    }
    function queue(){
        if('requestIdleCallback' in w){w.requestIdleCallback(load,{timeout:2000});}else{w.setTimeout(load,0);}
    }
    for(var j=0;j<events.length;j++){w.addEventListener(events[j],queue,{once:true,passive:true});}
    w.addEventListener('load',function(){w.setTimeout(queue,10000);},{once:true});
})(window,document,'script','https://mc.yandex.ru/metrika/tag.js?id=${yandexMetrikaId}','ym');`,
                    },
                ],
                postBodyTags: [
                    {
                        tagName: "noscript",
                        innerHTML: `<div><img src="https://mc.yandex.ru/watch/${yandexMetrikaId}" style="position:absolute; left:-9999px;" alt="" /></div>`,
                    },
                ],
            };
        },
    };
}

const config: Config = {
    title: "Laravel Feeds",
    tagline: "Fast export of large datasets to feeds",
    url: siteUrl,
    baseUrl: "/",
    favicon: "img/favicon.svg",
    organizationName: "TheDragonCode",
    projectName: "laravel-feeds",
    trailingSlash: true,
    onBrokenLinks: "throw",
    onBrokenAnchors: "throw",
    future: {
        v4: true,
    },
    headTags: [
        {
            tagName: "script",
            attributes: {
                type: "application/ld+json",
            },
            innerHTML: JSON.stringify(structuredData),
        },
    ],
    i18n: {
        path: process.env.DOCUSAURUS_I18N_PATH ?? "i18n",
        defaultLocale: "en",
        locales: ["en", "be", "de", "fr", "ko", "pt-BR", "ru", "uk", "zh-CN"],
        localeConfigs: {
            en: {
                label: "English",
                htmlLang: "en-US",
            },
            be: {
                label: "Беларуская",
                htmlLang: "be-BY",
            },
            de: {
                label: "Deutsch",
                htmlLang: "de-DE",
            },
            fr: {
                label: "Français",
                htmlLang: "fr-FR",
            },
            ko: {
                label: "한국어",
                htmlLang: "ko-KR",
            },
            "pt-BR": {
                label: "Português (Brasil)",
                htmlLang: "pt-BR",
            },
            ru: {
                label: "Русский",
                htmlLang: "ru-RU",
            },
            uk: {
                label: "Українська",
                htmlLang: "uk-UA",
            },
            "zh-CN": {
                label: "简体中文",
                htmlLang: "zh-CN",
            },
        },
    },
    plugins: [
        yandexMetrikaPlugin,
        [
            "@docusaurus/plugin-client-redirects",
            {
                redirects: legacyRedirects,
            },
        ],
    ],
    presets: [
        [
            "classic",
            {
                docs: {
                    routeBasePath: "/",
                    sidebarPath: "./sidebars.ts",
                    editUrl: `${repositoryUrl}/edit/main/docs/`,
                    editLocalizedFiles: true,
                    lastVersion: "current",
                    versions: {
                        current: {
                            label: "1.x",
                            path: "",
                            banner: "none",
                        },
                    },
                },
                blog: false,
                sitemap: {
                    changefreq: "weekly",
                    priority: 0.5,
                },
                theme: {
                    customCss: "./src/css/custom.css",
                },
            } satisfies Preset.Options,
        ],
    ],
    themeConfig: {
        image: "img/social-logo.png",
        metadata: [
            {
                name: "keywords",
                content:
                    "Laravel, feeds, data export, marketplaces, XML, CSV, JSON, PHP",
            },
            {
                name: "twitter:card",
                content: "summary_large_image",
            },
        ],
        colorMode: {
            defaultMode: "light",
            disableSwitch: false,
            respectPrefersColorScheme: true,
        },
        navbar: {
            title: "Laravel Feeds",
            logo: {
                alt: "Project logo",
                src: "img/logo.svg",
            },
            items: [
                {
                    to: "/introduction/",
                    label: "Documentation",
                    position: "left",
                    className: "navbar-docs-link",
                },
                {
                    type: "docsVersionDropdown",
                    position: "left",
                },
                {
                    type: "custom-docSearch",
                    position: "right",
                },
                {
                    type: "localeDropdown",
                    position: "right",
                },
                {
                    href: repositoryUrl,
                    label: "GitHub",
                    position: "right",
                },
            ],
        },
        footer: {
            style: "dark",
            links: [
                {
                    title: "Community",
                    items: [
                        {
                            label: "Telegram",
                            href: "https://t.me/dragon_code_news",
                        },
                        {
                            label: "Boosty",
                            href: "https://boosty.to/dragon-code",
                        },
                    ],
                },
                {
                    title: "Development",
                    items: [
                        {
                            label: "GitHub",
                            href: repositoryUrl,
                        },
                        {
                            label: "Issues",
                            href: `${repositoryUrl}/issues`,
                        },
                    ],
                },
                {
                    title: "Authors",
                    items: [
                        {
                            label: "The Dragon Code",
                            href: "https://github.com/TheDragonCode",
                        },
                    ],
                },
            ],
            copyright: `Copyright © ${new Date().getFullYear()} The Dragon Code`,
        },
        prism: {
            theme: prismThemes.vsLight,
            darkTheme: prismThemes.vsDark,
        },
    } satisfies Preset.ThemeConfig,
};

export default config;
