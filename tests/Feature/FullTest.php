<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Console\Commands\FeedGenerateCommand;
use Workbench\App\Data\NewsFakeData;
use Workbench\App\Feeds\FullFeed;

use function Pest\Laravel\artisan;

test('export', function (bool $pretty) {
    setPrettyXml($pretty);

    createNews(...NewsFakeData::toArray());

    $feed = app()->make(FullFeed::class);

    artisan(FeedGenerateCommand::class)->run();

    expect($feed->path())->toBeReadableFile();
    expect(file_get_contents($feed->path()))->toMatchSnapshot();
})->with('boolean');
