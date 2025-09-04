<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Commands\FeedInfoMakeCommand;

use function Pest\Laravel\artisan;

test('make feed item', function () {
    artisan(FeedInfoMakeCommand::class, [
        'name'    => 'FooBar',
        '--force' => true,
    ])->assertSuccessful()->run();

    expect('FooBar')->toMatchFeedInfoSnapshot();
});
