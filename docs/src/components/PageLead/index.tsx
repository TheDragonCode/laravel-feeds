import clsx from "clsx";
import type { ComponentPropsWithoutRef } from "react";

import styles from "./styles.module.css";

export default function PageLead({
    className,
    ...props
}: ComponentPropsWithoutRef<"div">) {
    return <div className={clsx(styles.lead, className)} {...props} />;
}
