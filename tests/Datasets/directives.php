<?php

declare(strict_types=1);

use Workbench\App\Feeds\Docs\ArrayDirectiveFeed;
use Workbench\App\Feeds\Docs\AttributesDirectiveFeed;
use Workbench\App\Feeds\Docs\CdataDirectiveFeed;
use Workbench\App\Feeds\Docs\MixedDirectiveFeed;
use Workbench\App\Feeds\Docs\ValueDirectiveFeed;

dataset('docs directives', [
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
