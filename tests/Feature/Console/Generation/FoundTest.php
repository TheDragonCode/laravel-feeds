<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Commands\FeedGenerateCommand;
use DragonCode\LaravelFeed\Models\Feed;

use function Pest\Laravel\artisan;

test('generates only the selected feed by slug', function (string $slug) {
    $command = artisan(FeedGenerateCommand::class, [
        'feed' => $slug,
    ]);

    getAllFeeds()->each(
        fn (Feed $feed) => $slug === $feed->slug
            ? $command->expectsOutputToContain($feed->class)
            : $command->doesntExpectOutputToContain($feed->class)
    );

    $command->assertSuccessful()->run();

    getAllFeeds()->each(
        fn (Feed $feed) => $slug === $feed->slug
            ? expect($feed)->toMatchGeneratedFeed()
            : expect($feed)->not->toMatchGeneratedFeed()
    );
})->with('generation slugs');

test('generates feeds with numeric slugs', function (string $slug) {
    $feed = Feed::firstOrFail();
    $feed->update(['title' => $slug]);

    artisan(FeedGenerateCommand::class, [
        'feed' => $slug,
    ])->expectsOutputToContain($feed->class)->assertSuccessful()->run();

    expect($feed)->toMatchGeneratedFeed();
})->with(['0', '001', '123', '1e2', '9999999999999999999999999999999999999999']);
