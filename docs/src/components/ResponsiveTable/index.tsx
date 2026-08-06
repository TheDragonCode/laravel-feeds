import clsx from "clsx";
import type { ComponentPropsWithoutRef } from "react";

import styles from "./styles.module.css";

type ResponsiveTableProps = ComponentPropsWithoutRef<"div"> & {
    label: string;
};

export default function ResponsiveTable({
    className,
    label,
    ...props
}: ResponsiveTableProps) {
    return (
        <div
            aria-label={label}
            className={clsx(styles.scroller, className)}
            role="region"
            tabIndex={0}
            {...props}
        />
    );
}
