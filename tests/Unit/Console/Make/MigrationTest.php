<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Console\Commands\FeedMakeCommand;

use function Pest\Laravel\artisan;

test('migration', function () {
    mockOperations(false);

    $operation = config('deploy-operations.path') . '/2025_09_03_015024_create_foo_bar_feed.php';
    $migration = database_path('migrations') . '/2025_09_03_015024_create_foo_bar_feed.php';

    artisan(FeedMakeCommand::class, [
        'name'    => 'FooBar',
        '--force' => true,
    ])
        ->expectsOutputToContain(resolvePath('app/Feeds/FooBarFeed.php] created successfully'))
        ->doesntExpectOutputToContain("Operation [$operation] created successfully.")
        ->expectsOutputToContain("Migration [$migration] created successfully.")
        ->assertSuccessful()
        ->run();

    expect($operation)->not->toBeFile();
    expect($migration)->toBeFile()->toMatchFileSnapshot();
});
