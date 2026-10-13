<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Commands\FeedGenerateCommand;
use DragonCode\LaravelFeed\Exceptions\FeedNotFoundException;
use DragonCode\LaravelFeed\Models\Feed;

use function Pest\Laravel\artisan;

test('rejects an unknown feed slug', function () {
    artisan(FeedGenerateCommand::class, [
        'feed' => 'unknown-feed',
    ])->run();
})->throws(FeedNotFoundException::class, 'Feed [unknown-feed] not found.');

test('does not fall back to a registration ID', function () {
    $feed = Feed::firstOrFail();

    expect(fn () => artisan(FeedGenerateCommand::class, [
        'feed' => (string) $feed->id,
    ])->run())->toThrow(FeedNotFoundException::class, 'Feed [' . $feed->id . '] not found.');
});

test('rejects a feed slug after its title has changed', function () {
    $feed = Feed::firstOrFail();
    $slug = $feed->slug;
    $feed->update(['title' => 'Renamed feed']);

    expect(fn () => artisan(FeedGenerateCommand::class, [
        'feed' => $slug,
    ])->run())->toThrow(FeedNotFoundException::class, 'Feed [' . $slug . '] not found.');
});
