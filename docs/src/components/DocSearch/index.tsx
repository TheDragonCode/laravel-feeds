import Translate, { translate } from "@docusaurus/Translate";
import Link from "@docusaurus/Link";
import useDocusaurusContext from "@docusaurus/useDocusaurusContext";
import clsx from "clsx";
import { useEffect, useMemo, useRef, useState } from "react";

import styles from "./styles.module.css";

type DocSearchProps = {
    className?: string;
    mobile?: boolean;
    onClick?: () => void;
};

type SearchRoute = {
    content: string;
    description: string;
    headings: string[];
    keywords: string[];
    route: string;
    title: string;
};

export default function DocSearch({
    className,
    mobile = false,
    onClick,
}: DocSearchProps) {
    const { i18n } = useDocusaurusContext();
    const dialogRef = useRef<HTMLDialogElement>(null);
    const inputRef = useRef<HTMLInputElement>(null);
    const [isReady, setIsReady] = useState(false);
    const [isOpen, setIsOpen] = useState(false);
    const [query, setQuery] = useState("");
    const [searchIndex, setSearchIndex] = useState<{
        locale: string;
        routes: SearchRoute[];
    } | null>(null);
    const activeSearchIndex =
        searchIndex?.locale === i18n.currentLocale ? searchIndex.routes : null;
    const searchableRoutes = useMemo(
        () =>
            (activeSearchIndex ?? []).map((route) => ({
                ...route,
                titleText: route.title.toLocaleLowerCase(i18n.currentLocale),
                summaryText: [
                    route.description,
                    ...route.keywords,
                    ...route.headings,
                ]
                    .join(" ")
                    .toLocaleLowerCase(i18n.currentLocale),
                searchText: [
                    route.title,
                    route.description,
                    ...route.keywords,
                    ...route.headings,
                    route.content,
                ]
                    .join(" ")
                    .toLocaleLowerCase(i18n.currentLocale),
            })),
        [activeSearchIndex, i18n.currentLocale],
    );
    const results = useMemo(() => {
        const normalizedQuery = query
            .trim()
            .toLocaleLowerCase(i18n.currentLocale);
        const relevance = (route: (typeof searchableRoutes)[number]) =>
            Number(route.titleText.includes(normalizedQuery)) * 2 +
            Number(route.summaryText.includes(normalizedQuery));

        return searchableRoutes
            .filter(
                (route) =>
                    normalizedQuery === "" ||
                    route.searchText.includes(normalizedQuery),
            )
            .sort((left, right) => relevance(right) - relevance(left))
            .slice(0, 8);
    }, [i18n.currentLocale, query, searchableRoutes]);

    useEffect(() => {
        setIsReady(true);
    }, []);

    useEffect(() => {
        if (!isOpen) {
            return;
        }

        dialogRef.current?.showModal();
        inputRef.current?.focus();
    }, [isOpen]);

    useEffect(() => {
        if (!isOpen || activeSearchIndex) {
            return;
        }

        let active = true;
        const locale = i18n.currentLocale;

        void fetch(`/search/${locale}.json`)
            .then(async (response) => {
                if (!response.ok) {
                    throw new Error(`Search index request failed: ${response.status}`);
                }

                return (await response.json()) as SearchRoute[];
            })
            .then((routes) => {
                if (active) {
                    setSearchIndex({ locale, routes });
                }
            })
            .catch(() => undefined);

        return () => {
            active = false;
        };
    }, [activeSearchIndex, i18n.currentLocale, isOpen]);

    useEffect(() => {
        if (mobile) {
            return;
        }

        const handleShortcut = (event: KeyboardEvent) => {
            if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === "k") {
                event.preventDefault();
                setIsOpen(true);
            }
        };

        window.addEventListener("keydown", handleShortcut);

        return () => window.removeEventListener("keydown", handleShortcut);
    }, [mobile]);

    const close = () => {
        dialogRef.current?.close();
        setIsOpen(false);
        setQuery("");
    };

    const handleResultClick = () => {
        close();
        onClick?.();
    };

    const search = (
        <>
            <button
                className={clsx(
                    mobile ? "menu__link" : "navbar__item navbar__link",
                    styles.trigger,
                    className,
                )}
                data-doc-search-trigger
                data-search-ready={isReady}
                onClick={() => setIsOpen(true)}
                type="button"
            >
                <span>
                    <Translate id="search.trigger">Search</Translate>
                </span>
                {!mobile && <kbd>Ctrl K</kbd>}
            </button>

            <dialog
                aria-label={translate({
                    id: "search.dialog.label",
                    message: "Search documentation",
                })}
                className={styles.dialog}
                onCancel={(event) => {
                    event.preventDefault();
                    close();
                }}
                onClick={(event) => {
                    if (event.target === event.currentTarget) {
                        close();
                    }
                }}
                ref={dialogRef}
            >
                <div className={styles.panel}>
                    <div className={styles.searchRow}>
                        <label className={styles.visuallyHidden} htmlFor={`doc-search-${mobile ? "mobile" : "desktop"}`}>
                            <Translate id="search.input.label">
                                Search documentation
                            </Translate>
                        </label>
                        <input
                            id={`doc-search-${mobile ? "mobile" : "desktop"}`}
                            onChange={(event) => setQuery(event.target.value)}
                            placeholder={translate({
                                id: "search.input.placeholder",
                                message: "Search guides, API, and troubleshooting",
                            })}
                            ref={inputRef}
                            type="search"
                            value={query}
                        />
                        <button onClick={close} type="button">
                            <Translate id="search.close">Close</Translate>
                        </button>
                    </div>

                    <div aria-live="polite" className={styles.results}>
                        {results.length > 0 ? (
                            results.map((route) => (
                                <Link
                                    key={route.route}
                                    onClick={handleResultClick}
                                    to={route.route}
                                >
                                    <strong>{route.title}</strong>
                                    <span>{route.description}</span>
                                </Link>
                            ))
                        ) : activeSearchIndex ? (
                            <p>
                                <Translate id="search.empty">
                                    No matching documentation found.
                                </Translate>
                            </p>
                        ) : null}
                    </div>
                </div>
            </dialog>
        </>
    );

    return mobile ? <li className="menu__list-item">{search}</li> : search;
}
