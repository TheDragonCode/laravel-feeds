<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Commands\FeedGenerateCommand;
use DragonCode\LaravelFeed\Models\Feed;
use Workbench\App\Feeds\Docs\ReceiptSitemapFeed;
use Workbench\App\Feeds\Docs\ReceiptYandexFeed;
use Workbench\App\Models\Product;

use function Pest\Laravel\artisan;

it('generate stub', function (string $feed, array $files, array $replaces = []): void {
    Product::factory()->count(2)->create();

    $model = Feed::create([
        'class' => $feed,
        'title' => $feed,
    ]);

    artisan(FeedGenerateCommand::class, ['feed' => $model->id])
        ->assertSuccessful()
        ->run();

    foreach ($files as $from => $to) {
        copyFeedFileToDoc($from, $to, $replaces, false);
    }
})->with([
    'sitemap' => [
        'feed' => ReceiptSitemapFeed::class,

        'files' => [
            'ReceiptSitemapFeed'           => 'receipt-sitemap-feed.php',
            'Items/ReceiptSitemapFeedItem' => 'receipt-sitemap-feed-item.php',
        ],

        'replaces' => [
            'ReceiptSitemapFeed'       => 'ProductFeed',
            'Workbench\App\Feeds\Docs' => 'App\Feeds\Sitemaps',

            '../../../../../../../../../docs/snippets/receipt-sitemap-feed.xml' => 'sitemaps/products.xml',
        ],
    ],

    'yandex' => [
        'feed' => ReceiptYandexFeed::class,

        'files' => [
            'ReceiptYandexFeed'           => 'receipt-yandex-feed.php',
            'Info/ReceiptYandexFeedInfo'  => 'receipt-yandex-feed-info.php',
            'Items/ReceiptYandexFeedItem' => 'receipt-yandex-feed-item.php',
        ],

        'replaces' => [
            'ReceiptYandexFeed'        => 'YandexFeed',
            'Workbench\App\Feeds\Docs' => 'App\Feeds',

            '../../../../../../../../../docs/snippets/receipt-yandex-feed.xml' => 'yandex.xml',
        ],
    ],
]);
