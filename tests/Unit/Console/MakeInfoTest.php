<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Console\Commands\FeedInfoMakeCommand;

use function Pest\Laravel\artisan;

test('make feed item', function () {
    deleteFeed('Info/FooBar');

    artisan(FeedInfoMakeCommand::class, [
        'name'    => 'FooBar',
        '--force' => true,
    ])->assertSuccessful()->run();

    expect('FooBar')->toMatchFeedInfoSnapshot();
});
