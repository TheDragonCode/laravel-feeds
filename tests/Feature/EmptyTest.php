<?php

use DragonCode\LaravelFeed\Console\Commands\FeedGenerateCommand;
use Tests\Fixtures\Feeds\EmptyFeed;

use function Pest\Laravel\artisan;

test('export', function () {
    $feed = app()->make(EmptyFeed::class);

    artisan(FeedGenerateCommand::class)->run();

    expect($feed->path())->toBeReadableFile();
    expect(file_get_contents($feed->path()))->toMatchSnapshot();
});
