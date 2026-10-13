<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Models\Feed;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Workbench\App\Feeds\EmptyFeed;

test('supports registrations before running the slug upgrade migration on a fresh installation', function () {
    config()->set('feeds.table.table', 'fresh_feeds');

    $migrations = dirname(__DIR__, 3) . '/database/migrations/';
    $original   = require $migrations . '2025_09_01_231655_create_feeds_table.php';
    $migration  = require $migrations . '2026_10_14_000000_add_slug_to_feeds_table.php';

    $original->up();

    $feed = Feed::create([
        'class' => EmptyFeed::class,
        'title' => 'Product Catalog',
    ]);

    $migration->up();

    expect($feed->fresh()->slug)->toBe('product-catalog');
});

test('backfills legacy registrations and rolls back on the configured table and connection', function (?string $connection) {
    if ($connection !== null) {
        config()->set('database.connections.' . $connection, [
            ...config('database.connections.sqlite'),
            'database' => ':memory:',
        ]);
    }

    config()->set([
        'feeds.table.connection' => $connection,
        'feeds.table.table'      => 'legacy_feeds',
    ]);

    $migrations = dirname(__DIR__, 3) . '/database/migrations/';
    $original   = require $migrations . '2025_09_01_231655_create_feeds_table.php';
    $migration  = require $migrations . '2026_10_14_000000_add_slug_to_feeds_table.php';
    $schema     = Schema::connection($connection);
    $table      = $schema->getConnection()->table('legacy_feeds');

    $original->up();

    $schema->table('legacy_feeds', fn (Blueprint $table) => $table->dropColumn('slug'));

    $table->insert(array_map(static fn (int $id): array => [
        'class'         => 'LegacyFeed' . $id,
        'title'         => $id % 2 === 0 ? 'Каталог товаров' : str_repeat('ß', 255),
        'expression'    => '0 * * * *',
        'is_active'     => true,
        'last_activity' => '2026-10-13 12:00:00',
        'created_at'    => '2026-10-12 12:00:00',
        'updated_at'    => '2026-10-13 12:00:00',
        'deleted_at'    => $id === 1001 ? '2026-10-14 12:00:00' : null,
    ], range(1, 1001)));

    $before = $table->orderBy('id')->get();

    $migration->up();

    expect($schema->getColumnType('legacy_feeds', 'slug'))->toBe('text');

    $after = $table->orderBy('id')->get()->map(function (object $feed): object {
        expect($feed->slug)->toBe(Str::slug($feed->title));

        unset($feed->slug);

        return $feed;
    });

    expect($after)->toEqual($before);

    $migration->down();

    expect($schema->hasColumn('legacy_feeds', 'slug'))->toBeFalse()
        ->and($table->orderBy('id')->get())->toEqual($before);
})->with([null, 'feed_migration']);
