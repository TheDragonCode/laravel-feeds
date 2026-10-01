<?php

declare(strict_types=1);

use Workbench\App\Feeds\Docs\ReceiptInstagramFeed;
use Workbench\App\Feeds\Docs\ReceiptRssFeed;
use Workbench\App\Feeds\Docs\ReceiptSitemapFeed;
use Workbench\App\Feeds\Docs\ReceiptYandexFeed;

dataset('docs receipts', [
    'sitemap' => [
        'feed' => ReceiptSitemapFeed::class,

        'files' => [
            'ReceiptSitemapFeed' => 'receipt-sitemap.php',
        ],

        'replaces' => [
            'ReceiptSitemapFeed'       => 'ProductFeed',
            'Workbench\App\Feeds\Docs' => 'App\Feeds\Sitemaps',
        ],
    ],

    'instagram' => [
        'feed' => ReceiptInstagramFeed::class,

        'files' => [
            'ReceiptInstagramFeed' => 'receipt-instagram.php',
        ],

        'replaces' => [
            'ReceiptInstagramFeed'     => 'InstagramFeed',
            'Workbench\App\Feeds\Docs' => 'App\Feeds',
        ],
    ],

    'yandex' => [
        'feed' => ReceiptYandexFeed::class,

        'files' => [
            'ReceiptYandexFeed' => 'receipt-yandex.php',
        ],

        'replaces' => [
            'ReceiptYandexFeed'        => 'YandexFeed',
            'Workbench\App\Feeds\Docs' => 'App\Feeds',
        ],
    ],

    'rss' => [
        'feed' => ReceiptRssFeed::class,

        'files' => [
            'ReceiptRssFeed' => 'receipt-rss.php',
        ],

        'replaces' => [
            'ReceiptRssFeed'           => 'RssFeed',
            'Workbench\App\Feeds\Docs' => 'App\Feeds',
        ],
    ],
]);
