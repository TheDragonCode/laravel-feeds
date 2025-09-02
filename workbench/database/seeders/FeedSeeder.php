<?php

declare(strict_types=1);

namespace Workbench\Database\Seeders;

use DragonCode\LaravelFeed\Models\Feed;
use Illuminate\Database\Seeder;
use Workbench\App\Feeds\EmptyFeed;
use Workbench\App\Feeds\FullFeed;
use Workbench\App\Feeds\PartialFeed;
use Workbench\App\Feeds\SitemapFeed;
use Workbench\App\Feeds\YandexFeed;

class FeedSeeder extends Seeder
{
    protected array $feeds = [
        EmptyFeed::class,
        FullFeed::class,
        PartialFeed::class,
        SitemapFeed::class,
        YandexFeed::class,
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

            'expression' => '* * * * *',
        ]);
    }
}
