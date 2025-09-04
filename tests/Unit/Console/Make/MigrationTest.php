<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Commands\FeedMakeCommand;

use function Pest\Laravel\artisan;

test('migration', function () {
    mockOperations(false);

    artisan(FeedMakeCommand::class, [
        'name'    => 'FooBar',
        '--force' => true,
    ])
        ->expectsOutputToContain(resolvePath('app/Feeds/FooBarFeed.php] created successfully'))
        ->doesntExpectOutputToContain('Operation')
        ->expectsOutputToContain('Migration')
        ->assertSuccessful()
        ->run();
});
