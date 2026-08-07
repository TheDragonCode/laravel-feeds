import MDXComponents from "@theme-original/MDXComponents";
import type { ComponentPropsWithoutRef } from "react";

import ApiSignature from "@site/src/components/ApiSignature";
import PageLead from "@site/src/components/PageLead";
import ResponsiveTable from "@site/src/components/ResponsiveTable";

function Table(props: ComponentPropsWithoutRef<"table">) {
    return <table {...props} tabIndex={0} />;
}

export default {
    ...MDXComponents,
    ApiSignature,
    PageLead,
    ResponsiveTable,
    table: Table,
};
