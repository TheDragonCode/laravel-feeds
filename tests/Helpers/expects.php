<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Commands\FeedGenerateCommand;
use DragonCode\LaravelFeed\Enums\FeedFormatEnum;

use function Pest\Laravel\artisan;

function expectFeedSnapshot(string $class, ?FeedFormatEnum $format = null, array $indexes = ['']): void
{
    $feed = findFeed($class);

    $instance = app($feed->class);
    $format ??= $instance->format();

    artisan(FeedGenerateCommand::class, [
        'feed' => $feed->id,
    ])->assertSuccessful()->run();

    foreach ($indexes as $index) {
        expect($instance->path($index))->toBeFile();

        $content = readFeedFile($instance->path($index));

        expect($content)->toMatchSnapshot();

        match ($format) {
            FeedFormatEnum::Json      => expect($content)->toBeJsonDocument(),
            FeedFormatEnum::JsonLines => expect($content)->toBeJsonLines(),
            FeedFormatEnum::Csv       => expect($content)->toBeCsv(),
            FeedFormatEnum::Rss       => expect($content)->toBeRss(),
            FeedFormatEnum::Xml       => expect($content)->toBeXml(),
        };
    }
}
