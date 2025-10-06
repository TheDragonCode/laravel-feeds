<?php

declare(strict_types=1);

namespace Workbench\Database\Seeders;

use DragonCode\LaravelFeed\Models\Feed;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Workbench\App\Feeds\CsvFeed;
use Workbench\App\Feeds\CsvInfoFeed;
use Workbench\App\Feeds\CsvRootFeed;
use Workbench\App\Feeds\CsvRootInfoFeed;
use Workbench\App\Feeds\EmptyFeed;
use Workbench\App\Feeds\FullFeed;
use Workbench\App\Feeds\JsonFeed;
use Workbench\App\Feeds\JsonInfoFeed;
use Workbench\App\Feeds\JsonLinesFeed;
use Workbench\App\Feeds\JsonLinesInfoFeed;
use Workbench\App\Feeds\JsonLinesRootFeed;
use Workbench\App\Feeds\JsonLinesRootInfoFeed;
use Workbench\App\Feeds\JsonRootFeed;
use Workbench\App\Feeds\JsonRootInfoFeed;
use Workbench\App\Feeds\ModelFeed;
use Workbench\App\Feeds\PartialFeed;
use Workbench\App\Feeds\RssFeed;
use Workbench\App\Feeds\RssInfoFeed;
use Workbench\App\Feeds\RssRootFeed;
use Workbench\App\Feeds\RssRootInfoFeed;
use Workbench\App\Feeds\SitemapFeed;
use Workbench\App\Feeds\SplitCsvFeed;
use Workbench\App\Feeds\SplitJsonFeed;
use Workbench\App\Feeds\SplitJsonLinesFeed;
use Workbench\App\Feeds\SplitMaxFilesFeed;
use Workbench\App\Feeds\SplitPerFileFeed;
use Workbench\App\Feeds\SplitRssFeed;
use Workbench\App\Feeds\SplitXmlFeed;
use Workbench\App\Feeds\YandexFeed;

use function fake;
use function implode;

class FeedSeeder extends Seeder
{
    protected array $feeds = [
        EmptyFeed::class,
        FullFeed::class,
        ModelFeed::class,
        PartialFeed::class,

        // Json
        JsonFeed::class,
        JsonInfoFeed::class,
        JsonRootFeed::class,
        JsonRootInfoFeed::class,

        // Json Lines
        JsonLinesFeed::class,
        JsonLinesInfoFeed::class,
        JsonLinesRootFeed::class,
        JsonLinesRootInfoFeed::class,

        // Csv
        CsvFeed::class,
        CsvInfoFeed::class,
        CsvRootFeed::class,
        CsvRootInfoFeed::class,

        // Rss
        RssFeed::class,
        RssInfoFeed::class,
        RssRootFeed::class,
        RssRootInfoFeed::class,

        // Receipts
        SitemapFeed::class,
        YandexFeed::class,

        // Split
        SplitPerFileFeed::class,
        SplitMaxFilesFeed::class,
        SplitCsvFeed::class,
        SplitJsonFeed::class,
        SplitJsonLinesFeed::class,
        SplitRssFeed::class,
        SplitXmlFeed::class,
    ];

    public function run(): void
    {
        foreach ($this->feeds as $feed) {
            $this->store($feed);
        }
    }

    protected function store(string $name): void
    {
        Feed::create([
            'class' => $name,
            'title' => $name,

            'expression' => $this->expression(),
        ]);
    }

    protected function expression(): string
    {
        return implode(' ', [
            $this->protectedMinute(),
            fake()->numberBetween(0, 23),
            fake()->numberBetween(1, 10),
            fake()->numberBetween(1, 12),
            fake()->numberBetween(2, 6),
        ]);
    }

    protected function protectedMinute(): int
    {
        $value = fake()->numberBetween(0, 59);

        if ($value === Carbon::now()->minute) {
            return $this->protectedMinute();
        }

        return $value;
    }
}
