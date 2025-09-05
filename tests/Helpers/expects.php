<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Commands\FeedGenerateCommand;

use function Pest\Laravel\artisan;

function expectFeedSnapshot(string $class, bool $isJson = false): void
{
    $feed = findFeed($class);

    $instance = app($feed->class);

    artisan(FeedGenerateCommand::class, [
        'feed' => $feed->id,
    ])->assertSuccessful()->run();

    expect($instance->path())->toBeFile();

    $content = file_get_contents($instance->path());

    if ($isJson) {
        expect($content)->toBeJson();
    }

    expect($content)->toMatchSnapshot();
}
