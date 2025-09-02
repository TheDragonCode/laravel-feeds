<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Console\Commands\FeedGenerateCommand;
use DragonCode\LaravelFeed\Exceptions\FeedNotFoundException;

use function Pest\Laravel\artisan;

test('not found', function () {
    artisan(FeedGenerateCommand::class, [
        'feed' => 123,
    ])->run();
})->throws(FeedNotFoundException::class, 'Feed [123] not found.');
