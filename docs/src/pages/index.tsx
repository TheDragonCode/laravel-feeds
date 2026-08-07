import Translate, { translate } from "@docusaurus/Translate";
import Link from "@docusaurus/Link";
import Layout from "@theme/Layout";
import { useRef, useState } from "react";

import styles from "./index.module.css";

const installCommand = "composer require dragon-code/laravel-feeds";
const phpExample = `final class ProductFeed extends Feed
{
    public function builder(): Builder
    {
        return Product::query();
    }
}`;
const xmlExample = `<product>
  <product>
    <id>1</id>
    <title>Desk lamp</title>
  </product>
</product>`;

function InstallCommand() {
    const codeRef = useRef<HTMLElement>(null);
    const [status, setStatus] = useState<"idle" | "copied" | "failed">("idle");

    const copy = async () => {
        try {
            await navigator.clipboard.writeText(installCommand);
            setStatus("copied");
            window.setTimeout(() => setStatus("idle"), 2000);
        } catch {
            setStatus("failed");
            codeRef.current?.focus();
        }
    };

    return (
        <div className={styles.installCommand}>
            <code ref={codeRef} tabIndex={-1}>
                {installCommand}
            </code>
            <button onClick={copy} type="button">
                {status === "copied" ? (
                    <Translate id="homepage.install.copied">Copied</Translate>
                ) : (
                    <Translate id="homepage.install.copy">Copy</Translate>
                )}
            </button>
            <span aria-live="polite" className={styles.visuallyHidden}>
                {status === "copied" && (
                    <Translate id="homepage.install.copySuccess">
                        Installation command copied to the clipboard.
                    </Translate>
                )}
                {status === "failed" && (
                    <Translate id="homepage.install.copyFailure">
                        Copy failed. Select the command manually.
                    </Translate>
                )}
            </span>
        </div>
    );
}

type FeatureCardProps = {
    description: React.ReactNode;
    href: string;
    title: React.ReactNode;
};

function FeatureCard({ description, href, title }: FeatureCardProps) {
    return (
        <Link className={styles.featureCard} to={href}>
            <strong>{title}</strong>
            <span>{description}</span>
        </Link>
    );
}

export default function HomePage() {
    const title = translate({
        id: "homepage.meta.title",
        message: "Laravel Feeds documentation",
    });
    const description = translate({
        id: "homepage.meta.description",
        message:
            "Export large Laravel datasets to XML, JSON, JSON Lines, CSV, RSS, and marketplace feeds.",
    });

    return (
        <Layout description={description} title={title}>
            <main>
                <section className={styles.hero}>
                    <div className={styles.heroInner}>
                        <div className={styles.heroCopy}>
                            <p className={styles.eyebrow}>
                                <Translate id="homepage.eyebrow">
                                    Laravel package · Documentation 1.x
                                </Translate>
                            </p>
                            <h1>
                                <Translate id="homepage.title">
                                    Export large datasets without loading them all into memory
                                </Translate>
                            </h1>
                            <p className={styles.summary}>
                                <Translate id="homepage.summary">
                                    Generate XML, JSON, JSON Lines, CSV, RSS, Sitemap,
                                    Instagram, and Yandex feeds through a streaming Laravel
                                    workflow.
                                </Translate>
                            </p>
                            <div className={styles.actions}>
                                <Link className="button button--primary button--lg" to="/installation/">
                                    <Translate id="homepage.action.start">
                                        Start in 5 minutes
                                    </Translate>
                                </Link>
                                <Link className="button button--secondary button--lg" to="/supported-formats/">
                                    <Translate id="homepage.action.formats">
                                        Choose a format
                                    </Translate>
                                </Link>
                            </div>
                            <InstallCommand />
                        </div>

                        <div className={styles.codePreview}>
                            <div>
                                <span>
                                    <Translate id="homepage.example.php">ProductFeed.php</Translate>
                                </span>
                                <pre tabIndex={0}>
                                    <code>{phpExample}</code>
                                </pre>
                            </div>
                            <div>
                                <span>
                                    <Translate id="homepage.example.output">Generated XML</Translate>
                                </span>
                                <pre tabIndex={0}>
                                    <code>{xmlExample}</code>
                                </pre>
                            </div>
                        </div>
                    </div>
                </section>

                <section aria-labelledby="paths-title" className={styles.paths}>
                    <div className={styles.sectionHeading}>
                        <p>
                            <Translate id="homepage.paths.eyebrow">Find your path</Translate>
                        </p>
                        <h2 id="paths-title">
                            <Translate id="homepage.paths.title">
                                Start with a task, then use the reference
                            </Translate>
                        </h2>
                    </div>
                    <div className={styles.featureGrid}>
                        <FeatureCard
                            description={
                                <Translate id="homepage.card.quick.description">
                                    Install the package and generate the first feed.
                                </Translate>
                            }
                            href="/introduction/"
                            title={
                                <Translate id="homepage.card.quick.title">Quick start</Translate>
                            }
                        />
                        <FeatureCard
                            description={
                                <Translate id="homepage.card.performance.description">
                                    Control chunks, memory, queues, and split files.
                                </Translate>
                            }
                            href="/performance/"
                            title={
                                <Translate id="homepage.card.performance.title">
                                    Large datasets
                                </Translate>
                            }
                        />
                        <FeatureCard
                            description={
                                <Translate id="homepage.card.recipes.description">
                                    Use ready patterns for Sitemap, RSS, Instagram, and Yandex.
                                </Translate>
                            }
                            href="/presets/"
                            title={
                                <Translate id="homepage.card.recipes.title">
                                    Formats and recipes
                                </Translate>
                            }
                        />
                        <FeatureCard
                            description={
                                <Translate id="homepage.card.api.description">
                                    Find exact methods, configuration keys, events, and failures.
                                </Translate>
                            }
                            href="/api/"
                            title={
                                <Translate id="homepage.card.api.title">API reference</Translate>
                            }
                        />
                    </div>
                </section>

                <section aria-label={translate({ id: "homepage.support.label", message: "Compatibility" })} className={styles.support}>
                    <span>PHP 8.2+</span>
                    <span>Laravel 11–13</span>
                    <span>XML · JSON · JSONL · CSV · RSS</span>
                    <Link to="/compatibility/">
                        <Translate id="homepage.support.details">Compatibility details</Translate>
                    </Link>
                </section>
            </main>
        </Layout>
    );
}
