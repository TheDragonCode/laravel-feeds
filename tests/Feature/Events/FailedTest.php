<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Commands\FeedGenerateCommand;
use DragonCode\LaravelFeed\Exceptions\FeedGenerationException;
use DragonCode\LaravelFeed\Models\Feed;
use Illuminate\Support\Facades\Event;
use Workbench\App\Feeds\FailedFeed;

use function Pest\Laravel\artisan;

test('failed', function () {
    Event::fake();

    $feed = Feed::create([
        'class' => FailedFeed::class,
        'title' => 'Failed',
    ]);

    artisan(FeedGenerateCommand::class, ['feed' => $feed->id])
        ->assertSuccessful()
        ->run();
})->throws(
    exception       : FeedGenerationException::class,
    exceptionMessage: 'Something went wrong while generating the feed.'
);

test('feed class link', function () {
    Event::fake();

    $feed = Feed::create([
        'class' => FailedFeed::class,
        'title' => 'Failed',
    ]);

    try {
        artisan(FeedGenerateCommand::class, ['feed' => $feed->id])
            ->assertSuccessful()
            ->run();
    } catch (FeedGenerationException $e) {
        expect($e->getMessage())->toContain('Something went wrong while generating the feed.');
        expect($e->getFeed())->toBe(FailedFeed::class);
    }
});
