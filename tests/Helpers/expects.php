<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Console\Commands\FeedGenerateCommand;

use function Pest\Laravel\artisan;

/**
 * @param  class-string<DragonCode\LaravelFeed\Feeds\Feed>  $feed
 */
function expectFeed(string $feed): void
{
    $instance = app($feed);

    artisan(FeedGenerateCommand::class)->assertSuccessful()->run();

    expect($instance->path())->toBeReadableFile();
    expect(file_get_contents($instance->path()))->toMatchSnapshot();
}
