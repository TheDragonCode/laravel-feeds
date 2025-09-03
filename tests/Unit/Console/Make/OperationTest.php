<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Console\Commands\FeedMakeCommand;

use function Pest\Laravel\artisan;

test('operation', function () {
    $operation = config('deploy-operations.path') . '/2025_09_03_015024_create_foo_bar_feed.php';
    $migration = database_path('migrations') . '/2025_09_03_015024_create_foo_bar_feed.php';

    artisan(FeedMakeCommand::class, [
        'name'    => 'FooBar',
        '--force' => true,
    ])
        ->expectsOutputToContain(resolvePath('app/Feeds/FooBarFeed.php] created successfully'))
        ->expectsOutputToContain("Operation [$operation] created successfully.")
        ->doesntExpectOutputToContain("Migration [$migration] created successfully.")
        ->assertSuccessful()
        ->run();

    expect($operation)->toBeFile()->toMatchFileSnapshot();
    expect($migration)->not->toBeFile();
});
