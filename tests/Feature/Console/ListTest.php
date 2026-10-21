<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Models\Feed;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Command\Command;
use Workbench\App\Feeds\CsvFeed;
use Workbench\App\Feeds\CsvInfoFeed;
use Workbench\App\Feeds\EmptyFeed;
use Workbench\App\Feeds\FullFeed;
use Workbench\App\Feeds\JsonFeed;
use Workbench\App\Feeds\JsonInfoFeed;
use Workbench\App\Feeds\ModelFeed;
use Workbench\App\Feeds\PartialFeed;
use Workbench\App\Feeds\RssFeed;

use function Pest\Laravel\artisan;

beforeEach(function () {
    Feed::withTrashed()->forceDelete();

    $this->nextRun    = getDefaultDateTime()->setTimezone(config()->string('app.timezone'))->addDay()->setTime(12, 0);
    $this->expression = $this->nextRun->format('i H d m *');
});

test('lists feed details in a table', function (bool $isActive, bool $isDeleted, ?string $lastActivity) {
    $feed = Feed::create([
        'class'         => EmptyFeed::class,
        'title'         => 'Product Catalog',
        'expression'    => $this->expression,
        'is_active'     => $isActive,
        'last_activity' => $lastActivity,
    ]);

    if ($isDeleted) {
        $feed->delete();
    }

    artisan('feed:list')
        ->expectsPromptsTable(
            ['id', 'slug', 'title', 'expression', 'next_run', 'is_active', 'is_deleted', 'last_activity'],
            [
                [
                    $feed->id,
                    'product-catalog',
                    'Product Catalog',
                    $this->expression,
                    $this->nextRun->toIso8601String(),
                    $isActive ? 'true' : 'false',
                    $isDeleted ? 'true' : 'false',
                    $lastActivity === null ? null : '2025-09-03T12:30:00+00:00',
                ],
            ]
        )
        ->doesntExpectOutputToContain('"tool":"feed:list"')
        ->assertSuccessful()
        ->run();
})->with('listed feed states');

test('returns feed details as JSON to an AI agent', function (bool $isActive, bool $isDeleted, ?string $lastActivity) {
    mockAgent(true);

    $feed = Feed::create([
        'class'         => EmptyFeed::class,
        'title'         => 'Product Catalog',
        'expression'    => $this->expression,
        'is_active'     => $isActive,
        'last_activity' => $lastActivity,
    ]);

    if ($isDeleted) {
        $feed->delete();
    }

    $status = Artisan::call('feed:list');
    $raw    = Artisan::output();
    $output = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);

    $expected = [
        'tool'   => 'feed:list',
        'result' => 'success',
        'feeds'  => [
            [
                'id'            => $feed->id,
                'slug'          => 'product-catalog',
                'title'         => 'Product Catalog',
                'expression'    => $this->expression,
                'next_run'      => $this->nextRun->toIso8601String(),
                'is_active'     => $isActive,
                'is_deleted'    => $isDeleted,
                'last_activity' => $lastActivity === null ? null : '2025-09-03T12:30:00+00:00',
            ],
        ],
    ];

    expect($status)->toBe(Command::SUCCESS)
        ->and($output)->toBe($expected)
        ->and($raw)->toBe(json_encode($expected, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES) . PHP_EOL);
})->with('listed feed states');

test('sorts active, inactive and deleted feeds by title within each group', function (bool $isAgent) {
    mockAgent($isAgent);

    $feeds = collect([
        [CsvFeed::class, 'Zulu', false, '2025-09-01 12:00:00'],
        [EmptyFeed::class, 'Zulu', true, null],
        [FullFeed::class, 'Alpha', false, null],
        [ModelFeed::class, 'Alpha', false, '2025-09-03 12:00:00'],
        [PartialFeed::class, 'Beta', true, '2025-09-02 12:00:00'],
        [CsvInfoFeed::class, 'Alpha', true, null],
        [JsonFeed::class, 'Zulu', false, null],
        [JsonInfoFeed::class, 'Alpha', true, null],
        [RssFeed::class, 'Alpha', true, '2025-09-02 12:00:00'],
    ])->map(function (array $attributes): Feed {
        [$class, $title, $isActive, $deletedAt] = $attributes;

        $feed = Feed::create([
            'class'      => $class,
            'title'      => $title,
            'expression' => $this->expression,
            'is_active'  => $isActive,
        ]);

        $feed->deleted_at = $deletedAt;
        $feed->save();

        return $feed;
    });

    $sorted = collect([5, 7, 1, 2, 6, 8, 4, 3, 0])
        ->map(fn (int $index): Feed => $feeds[$index]);

    if ($isAgent) {
        $status = Artisan::call('feed:list');
        $output = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        expect($status)->toBe(Command::SUCCESS)
            ->and(array_column($output['feeds'], 'id'))->toBe($sorted->pluck('id')->all());

        return;
    }

    artisan('feed:list')
        ->expectsPromptsTable(
            ['id', 'slug', 'title', 'expression', 'next_run', 'is_active', 'is_deleted', 'last_activity'],
            $sorted->map(fn (Feed $feed): array => [
                $feed->id,
                $feed->slug,
                $feed->title,
                $this->expression,
                $this->nextRun->toIso8601String(),
                $feed->is_active ? 'true' : 'false',
                $feed->trashed() ? 'true' : 'false',
                null,
            ])
        )
        ->assertSuccessful()
        ->run();
})->with([
    'console' => false,
    'agent'   => true,
]);

test('calculates the next run in the scheduler timezone', function (array $configuration, string $now, string $expression, string $expected) {
    config()->set($configuration);

    $this->travelTo(new DateTimeImmutable($now));

    mockAgent(true);

    Feed::create([
        'class'      => EmptyFeed::class,
        'title'      => 'Scheduled Feed',
        'expression' => $expression,
    ]);

    $status = Artisan::call('feed:list');
    $output = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

    expect($status)->toBe(Command::SUCCESS)
        ->and($output['feeds'][0]['next_run'])->toBe($expected);
})->with([
    'UTC interval'       => [['app.timezone' => 'UTC'], '2025-09-04T04:08:12+00:00', '*/15 * * * *', '2025-09-04T04:15:00+00:00'],
    'scheduler timezone' => [
        ['app.schedule_timezone' => 'America/New_York'],
        '2025-09-04T04:08:12+00:00', '0 9 * * *', '2025-09-04T09:00:00-04:00',
    ],
    'application timezone fallback' => [
        ['app.timezone' => 'Asia/Tokyo'],
        '2025-09-04T04:08:12+00:00', '0 9 * * *', '2025-09-05T09:00:00+09:00',
    ],
    'null scheduler timezone' => [
        ['app.schedule_timezone' => null, 'app.timezone' => 'Asia/Tokyo'],
        '2025-09-04T04:08:12+00:00', '0 9 * * *', '2025-09-05T09:00:00+09:00',
    ],
    'daylight saving starts' => [
        ['app.schedule_timezone' => 'America/New_York'],
        '2025-03-08T15:00:00+00:00', '0 9 * * *', '2025-03-09T09:00:00-04:00',
    ],
    'daylight saving ends' => [
        ['app.schedule_timezone' => 'America/New_York'],
        '2025-11-01T14:00:00+00:00', '0 9 * * *', '2025-11-02T09:00:00-05:00',
    ],
    'current occurrence is excluded' => [
        ['app.schedule_timezone' => 'America/New_York'],
        '2025-09-04T13:00:00+00:00', '0 9 * * *', '2025-09-05T09:00:00-04:00',
    ],
    'hourly alias' => [['app.timezone' => 'UTC'], '2025-09-04T04:08:12+00:00', '@hourly', '2025-09-04T05:00:00+00:00'],
]);

test('lists feeds with impossible cron expressions alongside runnable feeds', function (bool $isAgent) {
    mockAgent($isAgent);

    $impossible = Feed::create([
        'class'      => EmptyFeed::class,
        'title'      => 'Impossible Feed',
        'expression' => '0 0 31 2 *',
    ]);

    $scheduled = Feed::create([
        'class'      => FullFeed::class,
        'title'      => 'Scheduled Feed',
        'expression' => $this->expression,
    ]);

    if ($isAgent) {
        $status = Artisan::call('feed:list');
        $output = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        expect($status)->toBe(Command::SUCCESS)
            ->and(array_column($output['feeds'], 'next_run', 'id'))->toBe([
                $impossible->id => null,
                $scheduled->id  => $this->nextRun->toIso8601String(),
            ]);

        return;
    }

    artisan('feed:list')
        ->expectsPromptsTable(
            ['id', 'slug', 'title', 'expression', 'next_run', 'is_active', 'is_deleted', 'last_activity'],
            [
                [$impossible->id, 'impossible-feed', 'Impossible Feed', '0 0 31 2 *', null, 'true', 'false', null],
                [$scheduled->id, 'scheduled-feed', 'Scheduled Feed', $this->expression, $this->nextRun->toIso8601String(), 'true', 'false', null],
            ]
        )
        ->assertSuccessful()
        ->run();
})->with([
    'console' => false,
    'agent'   => true,
]);

test('reports an empty feed list to the console', function () {
    artisan('feed:list')
        ->expectsPromptsInfo('No feeds available')
        ->assertSuccessful()
        ->run();
});

test('returns an empty feed list to an AI agent', function () {
    mockAgent(true);

    $status = Artisan::call('feed:list');

    expect($status)->toBe(Command::SUCCESS)
        ->and(Artisan::output())->toBe('{"tool":"feed:list","result":"success","feeds":[]}' . PHP_EOL);
});

dataset('listed feed states', [
    'active without activity' => [true, false, null],
    'inactive with activity'  => [false, false, '2025-09-03 12:30:00'],
    'deleted with activity'   => [true, true, '2025-09-03 12:30:00'],
]);
