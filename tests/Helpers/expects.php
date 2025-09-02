<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Console\Commands\FeedGenerateCommand;

use function Pest\Laravel\artisan;

function expectFeed(string $class): void
{
    $feed = findFeed($class);

    $instance = app($feed->class);

    artisan(FeedGenerateCommand::class, [
        'class' => $feed->id,
    ])->assertSuccessful()->run();

    expect($instance->path())->toBeReadableFile();
    expect(file_get_contents($instance->path()))->toMatchSnapshot();
}
