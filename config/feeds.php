<?php

declare(strict_types=1);

return [
    /**
     * Pretty-print the generated feed output.
     *
     * When enabled, the resulting XML/JSON will include indentation and
     * human‑friendly formatting. Disable for slightly smaller payload size.
     *
     * By default, false
     */
    'pretty' => (bool) env('FEED_PRETTY', false),

    /**
     * Database table settings used by the package (e.g., for generation logs or state).
     */
    'table' => [
        /**
         * The database connection name to use.
         *
         * Should match a connection defined in config/database.php under
         * the "connections" array.
         */
        'connection' => env('DB_CONNECTION', 'sqlite'),

        /**
         * The database table name used by the package.
         */
        'table' => env('FEED_TABLE', 'feeds'),
    ],

    /**
     * Scheduling options for feed generation/update tasks.
     */
    'schedule' => [
        /**
         * Time To Live (in minutes) for the schedule lock or cache.
         *
         * Controls how frequently a scheduled job may be executed to avoid
         * overlapping or excessively frequent runs.
         */
        'ttl' => (int) env('FEED_SCHEDULE_TTL', 1440),

        /**
         * Run scheduled jobs in the background.
         *
         * When true, tasks will be dispatched to run asynchronously so they do
         * not block the current process. Set to false to run in the foreground.
         */
        'background' => (bool) env('FEED_SCHEDULE_RUN_BACKGROUND', true),
    ],
];
