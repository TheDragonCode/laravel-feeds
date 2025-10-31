<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Commands\FeedGenerateCommand;
use DragonCode\LaravelFeed\Enums\FeedFormatEnum;

use function Pest\Laravel\artisan;

function expectFeedSnapshot(string $class, FeedFormatEnum $format = FeedFormatEnum::Xml, array $indexes = ['']): void
{
    $feed = findFeed($class);

    $instance = app($feed->class);

    artisan(FeedGenerateCommand::class, [
        'feed' => $feed->id,
    ])->assertSuccessful()->run();

    foreach ($indexes as $index) {
        expect($instance->path($index))->toBeFile();

        $content = file_get_contents($instance->path($index));

        expect($content)->toMatchSnapshot();

        match ($format) {
            FeedFormatEnum::Json      => expect($content)->toBeJson(),
            FeedFormatEnum::JsonLines => expect($content)->toBeJsonLines(),
            FeedFormatEnum::Csv       => expect($content)->toBeCsv(),
            FeedFormatEnum::Rss       => expect($content)->toBeRss(),
            default                   => null
        };
    }
}
