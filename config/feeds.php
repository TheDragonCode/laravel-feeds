<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Transformers;

/**
 * --------------------------------------------------------------------------
 * Laravel Feeds configuration
 * --------------------------------------------------------------------------
 *
 * This file defines how feeds are generated and presented, including
 * formatting, persistence, scheduling, console UX and value transformers.
 * Adjust the options below according to your application needs.
 */
return [
    /**
     * --------------------------------------------------------------------------
     * Pretty-print the generated feed output
     * --------------------------------------------------------------------------
     *
     * When enabled, the resulting XML/JSON will include indentation and
     * human‑friendly formatting. Disable to reduce payload size.
     */
    'pretty' => (bool) env('FEED_PRETTY', false),

    /**
     * --------------------------------------------------------------------------
     * Output date/time options
     * --------------------------------------------------------------------------
     */
    'date' => [
        /**
         * Date/time format used when serializing timestamps to feeds.
         * Accepts any valid PHP date/time format string or constant.
         */
        'format' => DATE_ATOM,

        /** The timezone applied when formatting dates. */
        'timezone' => env('FEED_TIMEZONE', 'UTC'),
    ],

    /**
     * --------------------------------------------------------------------------
     * Database table settings used by the package (for logs or internal state)
     * --------------------------------------------------------------------------
     */
    'table' => [
        /**
         * The database connection name to use.
         * Should match a connection defined in config/database.php.
         */
        'connection' => env('DB_CONNECTION', 'sqlite'),

        /** The database table name used by the package. */
        'table' => env('FEED_TABLE', 'feeds'),
    ],

    /**
     * --------------------------------------------------------------------------
     * Scheduling options for feed generation/update tasks
     * --------------------------------------------------------------------------
     */
    'schedule' => [
        /**
         * Time-to-live (in minutes) for the schedule lock or cache.
         * Helps prevent overlapping or excessively frequent runs.
         */
        'ttl' => (int) env('FEED_SCHEDULE_TTL', 1440),

        /**
         * Run scheduled jobs in the background.
         * When true, tasks are dispatched asynchronously to avoid blocking.
         */
        'background' => (bool) env('FEED_SCHEDULE_RUN_BACKGROUND', true),
    ],

    /**
     * --------------------------------------------------------------------------
     * Queue options for feed generation
     * --------------------------------------------------------------------------
     *
     * Queue mode lets the console command dispatch feed generation jobs instead
     * of building feeds in the command process. It is disabled by default.
     */
    'queue' => [
        /** Dispatch feed generation jobs to Laravel Queue. */
        'enabled' => (bool) env('FEED_QUEUE_ENABLED', false),

        /**
         * The queue connection used for feed jobs.
         * Falls back to QUEUE_CONNECTION and then to the database connection.
         */
        'connection' => env('FEED_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'database')),

        /**
         * The queue name used for feed jobs.
         * A null value uses the connection's default queue.
         */
        'name' => env('FEED_QUEUE_NAME'),

        /** Maximum lifetime of a feed job's uniqueness lock, in seconds. */
        'unique_ttl' => (int) env('FEED_QUEUE_UNIQUE_TTL', 3600),
    ],

    /**
     * --------------------------------------------------------------------------
     * Console display options
     * --------------------------------------------------------------------------
     */
    'console' => [
        /** Show a progress bar when generating feeds in the console. */
        'progress_bar' => (bool) env('FEED_CONSOLE_PROGRESS_BAR_ENABLED', false),
    ],

    /**
     * --------------------------------------------------------------------------
     * Transformers List
     * --------------------------------------------------------------------------
     *
     * Transformers convert rich/complex values to simple scalar representations
     * suitable for feeds (XML/JSON). Transformers run in declaration order and
     * each transformer receives the value produced by the previous transformer.
     *
     * You may add your own transformers by implementing
     * `DragonCode\LaravelFeed\Contracts\Transformer` and registering the class
     * here.
     */
    'transformers' => [
        Transformers\BoolTransformer::class,
        Transformers\DateTimeTransformer::class,
        Transformers\EnumTransformer::class,
        // Transformers\NullTransformer::class,
    ],

    /**
     * --------------------------------------------------------------------------
     * Converters rules
     * --------------------------------------------------------------------------
     *
     * Converters define low-level serialization settings for specific output
     * formats. You can tweak encoder flags and other options here.
     */
    'converters' => [
        'json' => [
            /** JSON encoding flags used when exporting feeds to JSON. */
            'options' => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        ],

        'jsonl' => [
            /**
             * JSON encoding flags used when exporting feeds to JSON Lines format.
             * Pretty print is ignored for JSON Lines.
             */
            'options' => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        ],

        'csv' => [
            /** CSV specific options applied when exporting feeds in CSV format. */
            /** The delimiter used to separate values. Common values are "," or ";". */
            'delimiter'   => ';',
            'enclosure'   => '"',
            'escape'      => '',
            'line_ending' => PHP_EOL,
        ],
    ],
];
