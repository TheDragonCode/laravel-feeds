<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Console\Commands\FeedMakeCommand;

use function Pest\Laravel\artisan;

test('make feed', function () {
    deleteFeed('FooBar');

    artisan(FeedMakeCommand::class, [
        'name'    => 'FooBar',
        '--force' => true,
    ])
        ->expectsOutputToContain('app\Feeds\FooBarFeed.php] created successfully')
        ->doesntExpectOutputToContain('app\Feeds\Items')
        ->doesntExpectOutputToContain('app\Feeds\Info')
        ->assertSuccessful()
        ->run();

    expect('FooBar')->toMatchFeedSnapshot();
});

test('make with item', function () {
    deleteFeed('QweRty');
    deleteFeed('Items/QweRty');

    artisan(FeedMakeCommand::class, [
        'name'    => 'QweRty',
        '--item'  => true,
        '--force' => true,
    ])
        ->expectsOutputToContain('app\Feeds\QweRtyFeed.php] created successfully')
        ->expectsOutputToContain('app\Feeds\Items\QweRtyFeedItem.php] created successfully')
        ->doesntExpectOutputToContain('app\Feeds\Info')
        ->assertSuccessful()
        ->run();

    expect('QweRty')
        ->toMatchFeedSnapshot()
        ->toMatchFeedItemSnapshot();
});

test('make with info', function () {
    deleteFeed('QweRty');
    deleteFeed('Items/QweRty');

    artisan(FeedMakeCommand::class, [
        'name'    => 'QweRty',
        '--info'  => true,
        '--force' => true,
    ])
        ->expectsOutputToContain('app\Feeds\QweRtyFeed.php] created successfully')
        ->doesntExpectOutputToContain('app\Feeds\Items\QweRtyFeedItem.php] created successfully')
        ->expectsOutputToContain('app\Feeds\Info\QweRtyFeedInfo')
        ->assertSuccessful()
        ->run();

    expect('QweRty')
        ->toMatchFeedSnapshot()
        ->toMatchFeedInfoSnapshot();
});
