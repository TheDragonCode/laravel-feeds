<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Commands\FeedGenerateCommand;
use DragonCode\LaravelFeed\Models\Feed;
use Workbench\App\Feeds\Docs\ArrayDirectiveFeed;
use Workbench\App\Feeds\Docs\AttributesDirectiveFeed;
use Workbench\App\Feeds\Docs\CdataDirectiveFeed;
use Workbench\App\Feeds\Docs\MixedDirectiveFeed;
use Workbench\App\Feeds\Docs\ValueDirectiveFeed;

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
    '@attributes' => [
        'feed'  => AttributesDirectiveFeed::class,
        'files' => [
            'AttributesDirectiveFeed'           => 'advanced-directive-attributes.php',
            'Info/AttributesDirectiveFeedInfo'  => 'advanced-directive-attributes-info.php',
            'Items/AttributesDirectiveFeedItem' => 'advanced-directive-attributes-item.php',
        ],
    ],

    '@value' => [
        'feed'  => ValueDirectiveFeed::class,
        'files' => ['Items/ValueDirectiveFeedItem' => 'advanced-directive-value-item.php'],
    ],

    '@cdata' => [
        'feed'  => CdataDirectiveFeed::class,
        'files' => ['Items/CdataDirectiveFeedItem' => 'advanced-directive-cdata.php'],
    ],

    '@mixed' => [
        'feed'  => MixedDirectiveFeed::class,
        'files' => ['Items/MixedDirectiveFeedItem' => 'advanced-directive-mixed.php'],
    ],

    '@array' => [
        'feed'  => ArrayDirectiveFeed::class,
        'files' => ['Items/ArrayDirectiveFeedItem' => 'advanced-directive-array.php'],
    ],
]);
