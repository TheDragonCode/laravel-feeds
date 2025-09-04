<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Commands\FeedMakeCommand;

use function Pest\Laravel\artisan;

test('make with info', function () {
    artisan(FeedMakeCommand::class, [
        'name'    => 'QweRty',
        '--info'  => true,
        '--force' => true,
    ])
        ->expectsOutputToContain(resolvePath('app/Feeds/QweRtyFeed.php] created successfully'))
        ->doesntExpectOutputToContain(resolvePath('app/Feeds/Items/QweRtyFeedItem.php] created successfully'))
        ->expectsOutputToContain(resolvePath('app/Feeds/Info/QweRtyFeedInfo'))
        ->assertSuccessful()
        ->run();

    expect('QweRty')
        ->toMatchFeedSnapshot()
        ->toMatchFeedInfoSnapshot();
});
