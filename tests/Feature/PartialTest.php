<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Console\Commands\FeedGenerateCommand;
use Workbench\App\Data\NewsFakeData;
use Workbench\App\Feeds\FilledFeed;

use function Pest\Laravel\artisan;

test('export', function (bool $pretty) {
    setPrettyXml($pretty);

    createNews(static fn () => [
        'updated_at' => fake()->dateTimeBetween(endDate: '-1 month'),
    ]);

    createNews(...NewsFakeData::toArray());

    $feed = app()->make(FilledFeed::class);

    artisan(FeedGenerateCommand::class)->run();

    expect($feed->path())->toBeReadableFile();
    expect(file_get_contents($feed->path()))->toMatchSnapshot();
})->with('boolean');
