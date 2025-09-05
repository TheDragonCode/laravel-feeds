<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Transformers;

/**
 * Laravel Feeds configuration
 *
 * This file defines how feeds are generated and presented, including
 * formatting, persistence, scheduling, console UX and value transformers.
 * Adjust the options below or override them via environment variables.
 */
return [
    /**
     * Pretty-print the generated feed output.
     *
     * When enabled, the resulting XML/JSON will include indentation and
     * human‑friendly formatting. Disable for slightly smaller payload size.
     *
     * Default: false
     */
    'pretty' => (bool) env('FEED_PRETTY', false),

    /**
     * Output format options.
     */
    'formats' => [
        /**
         * Date/time format used when serializing timestamps to feeds.
         * You may use any PHP date format constant, e.g. DATE_ATOM, DATE_RFC3339
         * or a custom PHP date() format string.
         *
         * Default: DATE_ATOM
         */
        'date' => DATE_ATOM,
    ],

    /**
     * Database table settings used by the package (e.g., for generation logs or state).
     */
    'table' => [
        /**
         * The database connection name to use.
         *
         * Should match a connection defined in config/database.php under
         * the "connections" array.
         *
         * Default: sqlite
         */
        'connection' => env('DB_CONNECTION', 'sqlite'),

        /**
         * The database table name used by the package.
         *
         * Default: feeds
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
         *
         * Default: 1440 (24 hours)
         */
        'ttl' => (int) env('FEED_SCHEDULE_TTL', 1440),

        /**
         * Run scheduled jobs in the background.
         *
         * When true, tasks will be dispatched to run asynchronously so they do
         * not block the current process. Set to false to run in the foreground.
         *
         * Default: true
         */
        'background' => (bool) env('FEED_SCHEDULE_RUN_BACKGROUND', true),
    ],

    /**
     * Console display options.
     */
    'console' => [
        /**
         * Enables a progress bar when generating feeds in the console.
         *
         * When set to true, the feed:generate command will display a
         * progress bar showing the execution progress.
         *
         * Default: false
         */
        'progress_bar' => (bool) env('FEED_CONSOLE_PROGRESS_BAR_ENABLED', false),
    ],

    /**
     * Transformers convert rich/complex values to simple scalar representations
     * suitable for feeds (XML/JSON). Order matters: the first transformer that
     * supports the value will handle it.
     *
     * You may add your own transformers by implementing
     * `DragonCode\LaravelFeed\Contracts\Transformer` and registering the class
     * here, or publish a stub via the package's make command if available.
     */
    'transformers' => [
        Transformers\BoolTransformer::class,
        Transformers\DateTimeTransformer::class,
        Transformers\EnumTransformer::class,
        // Transformers\NullTransformer::class,
    ],
];
