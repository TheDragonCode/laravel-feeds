<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Commands\FeedMakeCommand;

use function Pest\Laravel\artisan;

test('make feed', function () {
    artisan(FeedMakeCommand::class, [
        'name'    => 'FooBar',
        '--force' => true,
    ])
        ->expectsOutputToContain(resolvePath('app/Feeds/FooBarFeed.php] created successfully'))
        ->doesntExpectOutputToContain(resolvePath('app/Feeds/Items'))
        ->doesntExpectOutputToContain(resolvePath('app/Feeds/Info'))
        ->assertSuccessful()
        ->run();

    expect('FooBar')->toMatchFeedSnapshot();
});
