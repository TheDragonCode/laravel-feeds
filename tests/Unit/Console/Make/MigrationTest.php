<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Console\Commands\FeedMakeCommand;

use function Pest\Laravel\artisan;

test('migration', function () {
    mockOperations(false);

    $operation = config('deploy-operations.path');
    $migration = database_path('migrations');

    artisan(FeedMakeCommand::class, [
        'name'    => 'FooBar',
        '--force' => true,
    ])
        ->expectsOutputToContain(resolvePath('app/Feeds/FooBarFeed.php] created successfully'))
        ->doesntExpectOutputToContain($operation)
        ->expectsOutputToContain($migration)
        ->assertSuccessful()
        ->run();
});
