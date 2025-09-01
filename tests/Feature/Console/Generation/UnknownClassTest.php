<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Console\Commands\FeedGenerateCommand;
use DragonCode\LaravelFeed\Exceptions\UnexpectedFeedException;
use Tests\TestCase;
use Workbench\App\Feeds\Info\YandexFeedInfo;
use Workbench\App\Feeds\Items\YandexFeedItem;
use Workbench\App\Providers\WorkbenchServiceProvider;

use function Pest\Laravel\artisan;

test('incorrect', function (string $feed) {
    artisan(FeedGenerateCommand::class, [
        'class' => $feed,
    ])->run();
})
    ->throws(UnexpectedFeedException::class)
    ->with([
        TestCase::class,
        WorkbenchServiceProvider::class,

        YandexFeedItem::class,
        YandexFeedInfo::class,
    ]);
