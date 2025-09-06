<?php

declare(strict_types=1);

use Workbench\App\Feeds\Docs\ReceiptInstagramFeed;
use Workbench\App\Feeds\Docs\ReceiptSitemapFeed;
use Workbench\App\Feeds\Docs\ReceiptYandexFeed;

dataset('docs receipts', [
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

    'instagram' => [
        'feed' => ReceiptInstagramFeed::class,

        'files' => [
            'ReceiptInstagramFeed'           => 'receipt-instagram-feed.php',
            'Items/ReceiptInstagramFeedItem' => 'receipt-instagram-feed-item.php',
        ],

        'replaces' => [
            'ReceiptInstagramFeed'     => 'InstagramFeed',
            'Workbench\App\Feeds\Docs' => 'App\Feeds',

            '../../../../../../../../../docs/snippets/receipt-instagram-feed.xml' => 'instagram.xml',
        ],
    ],
]);
