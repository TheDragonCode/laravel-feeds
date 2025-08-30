<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Console\Commands\FeedMakeCommand;

use function Pest\Laravel\artisan;

test('make feed', function () {
    deleteFeed('FooBar');

    artisan(FeedMakeCommand::class, [
        'name'    => 'FooBar',
        '--force' => true,
    ])->assertSuccessful()->run();

    expect('FooBar')->toMatchFeedSnapshot();
});

test('make with item', function () {
    deleteFeed('QweRty');
    deleteFeed('Items/QweRty');

    artisan(FeedMakeCommand::class, [
        'name'        => 'QweRty',
        '--with-item' => true,
        '--force'     => true,
    ])->assertSuccessful()->run();

    expect('QweRty')
        ->toMatchFeedSnapshot()
        ->toMatchFeedItemSnapshot();
});
