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

test('rejects ambiguous numeric feed IDs', function () {
    foreach ([
        0,
        1.0,
        '0',
        '-1',
        '1.9',
        '1e2',
        '+1',
        ' 1 ',
        '9999999999999999999999999999999999999999',
    ] as $id) {
        expect(fn () => artisan(FeedGenerateCommand::class, [
            'feed' => $id,
        ])->run())->toThrow(InvalidFeedArgumentException::class);
    }
});
