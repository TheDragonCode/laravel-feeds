<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Console\Commands\FeedMakeCommand;

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
