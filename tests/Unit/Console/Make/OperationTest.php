<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Commands\FeedMakeCommand;

use function Pest\Laravel\artisan;

test('operation', function () {
    artisan(FeedMakeCommand::class, [
        'name'    => 'FooBar',
        '--force' => true,
    ])
        ->expectsOutputToContain(resolvePath('app/Feeds/FooBarFeed.php] created successfully'))
        ->expectsOutputToContain('Operation')
        ->doesntExpectOutputToContain('Migration')
        ->assertSuccessful()
        ->run();
});
