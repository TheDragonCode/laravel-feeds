<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Commands\FeedGenerateCommand;
use DragonCode\LaravelFeed\Exceptions\InvalidFeedArgumentException;

use function Pest\Laravel\artisan;

test('rejects empty or non-normalized feed slugs', function (string $slug) {
    artisan(FeedGenerateCommand::class, [
        'feed' => $slug,
    ])->run();
})
    ->throws(InvalidFeedArgumentException::class, 'Feed slug must be a non-empty normalized string, [string] given.')
    ->with('generation invalid');

test('rejects non-string feed slugs', function () {
    foreach ([
        0,
        1,
        1.0,
        true,
        false,
        [],
        new stdClass,
    ] as $slug) {
        expect(fn () => artisan(FeedGenerateCommand::class, [
            'feed' => $slug,
        ])->run())->toThrow(InvalidFeedArgumentException::class);
    }
});
