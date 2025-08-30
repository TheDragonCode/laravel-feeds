<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Console\Commands\FeedGenerateCommand;
use Workbench\App\Feeds\EmptyFeed;

use function Pest\Laravel\artisan;

test('export', function (bool $pretty) {
    setPrettyXml($pretty);

    $feed = app()->make(EmptyFeed::class);

    artisan(FeedGenerateCommand::class)->run();

    expect($feed->path())->toBeReadableFile();
    expect(file_get_contents($feed->path()))->toMatchSnapshot();
})->with('boolean');
