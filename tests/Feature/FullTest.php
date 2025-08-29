<?php

use DragonCode\LaravelFeed\Console\Commands\FeedGenerateCommand;
use Tests\Fixtures\Data\NewsData;
use Tests\Fixtures\Feeds\FilledFeed;
use Tests\Fixtures\Models\News;

use function Pest\Laravel\artisan;

test('export', function () {
    News::factory()->count(3)->state(
        NewsData::toArray()
    )->createMany();

    $feed = app()->make(FilledFeed::class);

    artisan(FeedGenerateCommand::class)->run();

    expect($feed->path())->toBeReadableFile();
    expect(file_get_contents($feed->path()))->toMatchSnapshot();
});
