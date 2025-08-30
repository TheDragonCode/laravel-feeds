<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Console\Commands\FeedGenerateCommand;
use Workbench\App\Feeds\SitemapFeed;

use function Pest\Laravel\artisan;

test('export', function () {
    createProducts();

    $feed = app()->make(SitemapFeed::class);

    artisan(FeedGenerateCommand::class)->run();

    expect($feed->path())->toBeReadableFile();
    expect(file_get_contents($feed->path()))->toMatchSnapshot();
});
