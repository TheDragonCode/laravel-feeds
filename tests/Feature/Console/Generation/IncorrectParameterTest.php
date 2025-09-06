<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Commands\FeedGenerateCommand;
use DragonCode\LaravelFeed\Exceptions\InvalidFeedArgumentException;

use function Pest\Laravel\artisan;

test('incorrect', function (mixed $id) {
    artisan(FeedGenerateCommand::class, [
        'feed' => $id,
    ])->run();
})
    ->throws(InvalidFeedArgumentException::class, 'Feed ID must be of type integer, [string] given.')
    ->with('generation invalid');
