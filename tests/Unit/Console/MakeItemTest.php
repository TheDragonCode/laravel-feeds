<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Console\Commands\FeedItemMakeCommand;

use function Pest\Laravel\artisan;

test('make feed item', function () {
    deleteFeed('Items/FooBar');

    artisan(FeedItemMakeCommand::class, [
        'name'    => 'FooBar',
        '--force' => true,
    ])->assertSuccessful()->run();

    expect('FooBar')->toMatchFeedItemSnapshot();
});
