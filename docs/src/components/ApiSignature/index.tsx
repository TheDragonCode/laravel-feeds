import clsx from "clsx";
import type { ComponentPropsWithoutRef } from "react";

import styles from "./styles.module.css";

export default function ApiSignature({
    className,
    ...props
}: ComponentPropsWithoutRef<"code">) {
    return <code className={clsx(styles.signature, className)} {...props} />;
}
