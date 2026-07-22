<?php

declare(strict_types=1);

use App\Feeds\FooBarFeed;
use DragonCode\LaravelFeed\Commands\FeedMakeCommand;
use DragonCode\LaravelFeed\Models\Feed;
use DragonCode\LaravelFeed\Queries\FeedQuery;
use Illuminate\Filesystem\Filesystem;
use Workbench\App\Feeds\EmptyFeed;

use function Pest\Laravel\artisan;

test('generated feed registration migration is reversible', function () {
    Feed::query()->forceDelete();

    mockOperations(false);

    artisan(FeedMakeCommand::class, [
        'name'    => 'FooBar',
        '--force' => true,
    ])->assertSuccessful()->run();

    require_once app_path('Feeds/FooBarFeed.php');

    $migrationPath = database_path('migrations');
    $migrations    = (new Filesystem)->glob($migrationPath . '/*_create_foo_bar_feed.php');

    expect($migrations)->toHaveCount(1);

    app(FeedQuery::class)->create(
        class: EmptyFeed::class,
        title: 'Unrelated feed'
    );

    $options = [
        '--path'     => $migrationPath,
        '--realpath' => true,
    ];

    artisan('migrate', $options)->assertSuccessful()->run();

    expect(Feed::query()->whereClass(FooBarFeed::class)->exists())->toBeTrue()
        ->and(Feed::query()->whereClass(EmptyFeed::class)->exists())->toBeTrue();

    Feed::query()->whereClass(FooBarFeed::class)->forceDelete();

    artisan('migrate:rollback', $options)->assertSuccessful()->run();

    expect(Feed::withTrashed()->whereClass(FooBarFeed::class)->exists())->toBeFalse()
        ->and(Feed::query()->whereClass(EmptyFeed::class)->exists())->toBeTrue();

    artisan('migrate', $options)->assertSuccessful()->run();

    expect(Feed::query()->whereClass(FooBarFeed::class)->exists())->toBeTrue()
        ->and(Feed::query()->whereClass(EmptyFeed::class)->exists())->toBeTrue();

    artisan('migrate:rollback', $options)->assertSuccessful()->run();

    expect(Feed::withTrashed()->whereClass(FooBarFeed::class)->exists())->toBeFalse()
        ->and(Feed::query()->whereClass(EmptyFeed::class)->exists())->toBeTrue();

    artisan('migrate:rollback', $options)->assertSuccessful()->run();

    expect(Feed::query()->whereClass(EmptyFeed::class)->exists())->toBeTrue();
});
