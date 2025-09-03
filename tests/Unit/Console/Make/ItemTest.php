<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Console\Commands\FeedMakeCommand;

use function Pest\Laravel\artisan;

test('make with item', function () {
    artisan(FeedMakeCommand::class, [
        'name'    => 'QweRty',
        '--item'  => true,
        '--force' => true,
    ])
        ->expectsOutputToContain(resolvePath('app/Feeds/QweRtyFeed.php] created successfully'))
        ->expectsOutputToContain(resolvePath('app/Feeds/Items/QweRtyFeedItem.php] created successfully'))
        ->doesntExpectOutputToContain(resolvePath('app/Feeds/Info'))
        ->assertSuccessful()
        ->run();

    expect('QweRty')
        ->toMatchFeedSnapshot()
        ->toMatchFeedItemSnapshot();
});
