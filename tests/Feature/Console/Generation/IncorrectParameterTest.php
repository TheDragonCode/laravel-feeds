<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Console\Commands\FeedGenerateCommand;
use DragonCode\LaravelFeed\Exceptions\FeedNotFoundException;

use function Pest\Laravel\artisan;

test('incorrect', function (mixed $name) {
    artisan(FeedGenerateCommand::class, [
        'class' => $name,
    ])->run();
})
    ->throws(FeedNotFoundException::class)
    ->with([
        'foo=bar',
        'foo+bar',
        'foo bar',
        '123',
        123,
    ]);

test('may be correct', function (mixed $name) {
    $command = artisan(FeedGenerateCommand::class, [
        'class' => $name,
    ]);

    config()
        ?->collection('feeds.channels')
        ?->keys()
        ?->each(fn (string $feed) => $command->expectsOutputToContain($feed));

    $command->assertSuccessful()->run();

    config()
        ?->collection('feeds.channels')
        ?->keys()
        ?->each(fn (string $feed) => expect(app($feed)->path())->toBeReadableFile());
})->with([
    '',
    0,
    null,
]);
