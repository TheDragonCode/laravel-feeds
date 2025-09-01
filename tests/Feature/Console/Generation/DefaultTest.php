<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Console\Commands\FeedGenerateCommand;

use function Pest\Laravel\artisan;

test('generate', function () {
    $command = artisan(FeedGenerateCommand::class);

    config()
        ?->collection('feeds.channels')
        ?->keys()
        ?->each(fn (string $feed) => $command->expectsOutputToContain($feed));

    $command->assertSuccessful()->run();

    config()
        ?->collection('feeds.channels')
        ?->keys()
        ?->each(fn (string $feed) => expect(app($feed)->path())->toBeReadableFile());
});
