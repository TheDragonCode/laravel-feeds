<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Commands\FeedGenerateCommand;
use DragonCode\LaravelFeed\Models\Feed;
use Illuminate\Filesystem\Filesystem;

use function Pest\Laravel\artisan;

test('success', function () {
    $filesystem = new Filesystem;

    $feed = Feed::firstOrFail();

    $path  = app($feed->class)->path();
    $draft = app($feed->class)->path() . '.draft';

    $filesystem->ensureDirectoryExists(dirname($path));

    $filesystem->put($path, 'foo');
    $filesystem->put($draft, 'bar');

    artisan(FeedGenerateCommand::class, [
        'feed' => $feed->id,
    ])->assertSuccessful()->run();

    expect($feed)->toMatchGeneratedFeed();
});
