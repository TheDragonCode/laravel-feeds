<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Commands\FeedGenerateCommand;
use DragonCode\LaravelFeed\Enums\FeedFormatEnum;

use function Pest\Laravel\artisan;

function expectFeedSnapshot(string $class, FeedFormatEnum $format = FeedFormatEnum::Xml): void
{
    $feed = findFeed($class);

    $instance = app($feed->class);

    artisan(FeedGenerateCommand::class, [
        'feed' => $feed->id,
    ])->assertSuccessful()->run();

    expect($instance->path())->toBeFile();

    $content = file_get_contents($instance->path());

    match ($format) {
        FeedFormatEnum::Json      => expect($content)->toBeJson(),
        FeedFormatEnum::JsonLines => expect($content)->toBeJsonLines(),
        FeedFormatEnum::Csv       => expect($content)->toBeCsv(),
        default                   => null
    };

    expect($content)->toMatchSnapshot();
}
