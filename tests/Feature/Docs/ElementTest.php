<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Commands\FeedGenerateCommand;
use DragonCode\LaravelFeed\Models\Feed;
use Workbench\App\Feeds\Docs\AttributeFeed;
use Workbench\App\Feeds\Docs\HeaderFooterFeed;
use Workbench\App\Feeds\Docs\InfoMethodFeed;
use Workbench\App\Feeds\Docs\RootElementFeed;

use function Pest\Laravel\artisan;

it('generate stub', function (string $feed, array $files) {
    $model = Feed::create([
        'class' => $feed,
        'title' => $feed,
    ]);

    artisan(FeedGenerateCommand::class, ['feed' => $model->id])
        ->assertSuccessful()
        ->run();

    foreach ($files as $from => $to) {
        copyFeedFileToDoc($from, $to);
    }
})->with([
    'root' => [
        'feed'  => RootElementFeed::class,
        'files' => ['RootElementFeed' => 'advanced-element-root.php'],
    ],

    'info' => [
        'feed'  => InfoMethodFeed::class,
        'files' => [
            'InfoMethodFeed'          => 'advanced-element-info.php',
            'Info/InfoMethodFeedInfo' => 'advanced-element-info-info.php',
        ],
    ],

    'header & footer' => [
        'feed'  => HeaderFooterFeed::class,
        'files' => ['HeaderFooterFeed' => 'advanced-element-header-footer.php'],
    ],

    'attributes' => [
        'feed'  => AttributeFeed::class,
        'files' => [
            'AttributeFeed'           => 'advanced-element-attribute.php',
            'Items/AttributeFeedItem' => 'advanced-element-attribute-item.php',
        ],
    ],
]);
