<?php

declare(strict_types=1);

namespace Workbench\Database\Seeders;

use DragonCode\LaravelFeed\Models\Feed;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Workbench\App\Feeds\EmptyFeed;
use Workbench\App\Feeds\FullFeed;
use Workbench\App\Feeds\ModelFeed;
use Workbench\App\Feeds\PartialFeed;
use Workbench\App\Feeds\SitemapFeed;
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
