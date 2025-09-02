<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Console\Commands\FeedGenerateCommand;
use DragonCode\LaravelFeed\Models\Feed;

use function Pest\Laravel\artisan;

test('incorrect', function (mixed $name) {
    artisan(FeedGenerateCommand::class, [
        'feed' => $name,
    ])->run();
})->with([
    'foo bar',
    '123',
    123,
]);

test('will be correct', function (int $id) {
    $command = artisan(FeedGenerateCommand::class, [
        'feed' => $id,
    ]);

    getAllFeeds()->each(
        fn (Feed $feed) => $id === $feed->id
            ? $command->expectsOutputToContain($feed->class)
            : $command->doesntExpectOutputToContain($feed->class)
    );

    $command->assertSuccessful()->run();

    getAllFeeds()->each(
        fn (Feed $feed) => $id === $feed->id
            ? expect(app($feed->class)->path())->toBeReadableFile()
            : expect(app($feed->class)->path())->not->toBeReadableFile()
    );
})->with([
    fn () => Feed::query()->latest()->first()->id,
    fn () => Feed::query()->oldest()->first()->id,
]);
