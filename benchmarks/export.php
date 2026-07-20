<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Data\GenerationResultData;
use DragonCode\LaravelFeed\Enums\FeedFormatEnum;
use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Helpers\ConverterHelper;
use DragonCode\LaravelFeed\Queries\FeedQuery;
use DragonCode\LaravelFeed\Services\FilesystemService;
use DragonCode\LaravelFeed\Services\GeneratorService;
use DragonCode\LaravelFeed\Transformers\BoolTransformer;
use DragonCode\LaravelFeed\Transformers\DateTimeTransformer;
use DragonCode\LaravelFeed\Transformers\EnumTransformer;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Support\LazyCollection;
use Spatie\TemporaryDirectory\TemporaryDirectory;

require __DIR__ . '/../vendor/autoload.php';

final class ExportBenchmarkModel extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    public function toArray(): array
    {
        return $this->getAttributes();
    }
}

final class ExportBenchmarkFeed extends Feed
{
    protected FeedFormatEnum $format = FeedFormatEnum::JsonLines;

    public function __construct(
        Application $laravel,
        protected Builder $query,
        protected string $target,
    ) {
        parent::__construct($laravel);
    }

    public function builder(): Builder
    {
        return $this->query;
    }

    public function filename(): string
    {
        return basename($this->target);
    }

    public function path(int|string $suffix = ''): string
    {
        return $this->target;
    }
}

final class ExportBenchmarkGenerator extends GeneratorService
{
    protected function setLastActivity(Feed $feed): void {}

    protected function started(Feed $feed): void {}

    protected function finished(Feed $feed, GenerationResultData $result): void {}
}

function benchmarkBuilder(int $rows): Builder
{
    $createdAt = new DateTimeImmutable('2026-01-01T00:00:00+00:00');

    $models = LazyCollection::make(function () use ($rows, $createdAt) {
        for ($id = 1; $id <= $rows; $id++) {
            $model = new ExportBenchmarkModel;
            $model->setRawAttributes([
                'id'          => $id,
                'sku'         => 'SKU-' . $id,
                'title'       => 'Benchmark product ' . $id,
                'price'       => ($id % 10000) / 100,
                'active'      => $id % 2 === 0,
                'created_at'  => $createdAt,
                'updated_at'  => $createdAt,
                'category'    => 'category-' . ($id % 25),
                'stock'       => $id % 500,
                'description' => 'Representative export field set ' . $id,
            ]);

            yield $model;
        }
    });

    $builder = Mockery::mock(Builder::class);
    $builder->shouldNotReceive('count');
    $builder->shouldReceive('lazyById')->once()->with(1000)->andReturn($models);

    return $builder;
}

function benchmarkMedian(array $values): float
{
    sort($values, SORT_NUMERIC);

    $middle = intdiv(count($values), 2);

    if (count($values) % 2 === 1) {
        return $values[$middle];
    }

    return ($values[$middle - 1] + $values[$middle]) / 2;
}

function benchmarkRun(
    Application $application,
    Filesystem $files,
    string $target,
    int $rows,
): array {
    $files->delete($target);

    $feed       = new ExportBenchmarkFeed($application, benchmarkBuilder($rows), $target);
    $filesystem = new FilesystemService($files);
    $generator  = new ExportBenchmarkGenerator(
        $filesystem,
        new ConverterHelper($application),
        Mockery::mock(FeedQuery::class),
    );

    gc_collect_cycles();
    memory_reset_peak_usage();

    $startedAt = hrtime(true);
    $result    = $generator->feed($feed);
    $seconds   = (hrtime(true) - $startedAt) / 1_000_000_000;
    $records   = array_sum($result->records);

    if ($records !== $rows) {
        throw new RuntimeException("Expected [$rows] records, generated [$records].");
    }

    return [
        'seconds'         => $seconds,
        'rows_per_second' => $rows / $seconds,
        'output_bytes'    => $files->size($target),
        'peak_memory'     => memory_get_peak_usage(true),
    ];
}

$options    = getopt('', ['rows::', 'iterations::']);
$rows       = (int) ($options['rows'] ?? 100000);
$iterations = (int) ($options['iterations'] ?? 3);

if ($rows < 1) {
    throw new InvalidArgumentException('The row count must be greater than zero.');
}

if ($iterations < 1) {
    throw new InvalidArgumentException('The iteration count must be greater than zero.');
}

$temporary = (new TemporaryDirectory)
    ->name('laravel-feeds-export-benchmark-' . bin2hex(random_bytes(8)))
    ->create();
$files       = new Filesystem;
$storage     = $temporary->path('storage');
$target      = $temporary->path('feed.jsonl');
$application = new Application($temporary->path());

$files->ensureDirectoryExists($storage . '/framework/cache/laravel-feeds');

$application->useStoragePath($storage);
$application->instance('config', new Repository([
    'feeds' => [
        'pretty'       => false,
        'transformers' => [
            BoolTransformer::class,
            DateTimeTransformer::class,
            EnumTransformer::class,
        ],
        'date' => [
            'format'   => DATE_ATOM,
            'timezone' => 'UTC',
        ],
        'converters' => [
            'jsonl' => [
                'options' => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            ],
        ],
    ],
]));

Container::setInstance($application);

$results = [];

try {
    foreach (range(1, $iterations) as $iteration) {
        $results[] = ['iteration' => $iteration] + benchmarkRun($application, $files, $target, $rows);
    }

    $summary = [
        'rows'                   => $rows,
        'fields'                 => 10,
        'iterations'             => $iterations,
        'median_seconds'         => benchmarkMedian(array_column($results, 'seconds')),
        'median_rows_per_second' => benchmarkMedian(array_column($results, 'rows_per_second')),
        'output_bytes'           => $results[0]['output_bytes'],
        'peak_memory'            => max(array_column($results, 'peak_memory')),
    ];

    echo json_encode(
        ['summary' => $summary, 'runs' => $results],
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
    ) . PHP_EOL;
} finally {
    Mockery::close();
    $temporary->delete();
}
