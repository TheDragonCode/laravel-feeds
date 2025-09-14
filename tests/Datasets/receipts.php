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
            'ReceiptSitemapFeed' => 'receipt-sitemap-feed.php',
        ],

        'replaces' => [
            'ReceiptSitemapFeed'       => 'ProductFeed',
            'Workbench\App\Feeds\Docs' => 'App\Feeds\Sitemaps',

            '\'../../../../../../../../../docs/snippets/receipt-sitemap-feed.xml\'' => '\'sitemaps/\' . parent::filename()',
        ],
    ],

    'instagram' => [
        'feed' => ReceiptInstagramFeed::class,

        'files' => [
            'ReceiptInstagramFeed' => 'receipt-instagram-feed.php',
        ],

        'replaces' => [
            'ReceiptInstagramFeed'     => 'InstagramFeed',
            'Workbench\App\Feeds\Docs' => 'App\Feeds',

            '../../../../../../../../../docs/snippets/receipt-instagram-feed.xml' => 'instagram.xml',
        ],
    ],

    'yandex' => [
        'feed' => ReceiptYandexFeed::class,

        'files' => [
            'ReceiptYandexFeed' => 'receipt-yandex-feed.php',
        ],

        'replaces' => [
            'ReceiptYandexFeed'        => 'YandexFeed',
            'Workbench\App\Feeds\Docs' => 'App\Feeds',

            '../../../../../../../../../docs/snippets/receipt-yandex-feed.xml' => 'yandex.xml',
        ],
    ],

    'rss' => [
        'feed' => ReceiptRssFeed::class,

        'files' => [
            'ReceiptRssFeed' => 'receipt-rss-feed.php',
        ],

        'replaces' => [
            'ReceiptRssFeed'           => 'RssFeed',
            'Workbench\App\Feeds\Docs' => 'App\Feeds',

            '../../../../../../../../../docs/snippets/receipt-rss-feed.xml' => 'rss.xml',
        ],
    ],
]);
