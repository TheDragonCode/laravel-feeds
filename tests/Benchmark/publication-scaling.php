<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Services\FilesystemService;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Application;
use League\Flysystem\DecoratedAdapter;
use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Spatie\TemporaryDirectory\TemporaryDirectory;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

if ($argc < 2 || $argc > 3) {
    throw new RuntimeException('Usage: php tests/Benchmark/publication-scaling.php baseline-source.php [runs]');
}

$runs = filter_var($argv[2] ?? 3, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

if ($runs === false) {
    throw new RuntimeException('Run count must be a positive integer.');
}

final class PublicationBenchmarkRemoteAdapter extends DecoratedAdapter {}

function publicationScalingMedian(array $times): float
{
    sort($times);

    $middle = intdiv(count($times), 2);

    return count($times) % 2 === 0 ? ($times[$middle - 1] + $times[$middle]) / 2 : $times[$middle];
}

$workspace = (new TemporaryDirectory)->create();

try {
    $source = file_get_contents($argv[1]);

    if ($source === false) {
        throw new RuntimeException('Unable to read the baseline source.');
    }

    $source = str_replace(
        'namespace DragonCode\\LaravelFeed\\Services;',
        'namespace PublicationScalingBaseline;',
        $source,
        $replacements,
    );

    if ($replacements !== 1) {
        throw new RuntimeException('Baseline must be a FilesystemService source file.');
    }

    $baseline = $workspace->path('FilesystemService-baseline.php');

    file_put_contents($baseline, $source);

    require $baseline;

    new Application($workspace->path())->useStoragePath($workspace->path('storage'));

    $classes = [
        'baseline'  => PublicationScalingBaseline\FilesystemService::class,
        'candidate' => FilesystemService::class,
    ];

    fputcsv(STDOUT, ['scope', 'source', 'backend', 'parts', 'registry_or_phase', 'runs', 'median_ms'], escape: '');

    foreach ($classes as $label => $class) {
        foreach ([false, true] as $remote) {
            $backend = $remote ? 'remote' : 'local';
            $service = new $class(new Filesystem);
            $next    = new ReflectionMethod($class, $remote ? 'nextStorageOwnership' : 'nextOwnership');

            foreach ([100, 500, 1000] as $size) {
                $publication = $remote ? 'feeds/feed.json' : $workspace->path('feed.json');
                $targets     = [];
                $existing    = [];

                foreach (range(1, $size) as $index) {
                    $targets[] = dirname($publication) . DIRECTORY_SEPARATOR . "feed-$index.json";

                    $existing["feed-$index.json"]    = 'feed.json';
                    $existing["foreign-$index.json"] = 'foreign.json';
                }

                foreach (['empty' => [], 'existing' => $existing] as $registry => $ownership) {
                    $times    = [];
                    $expected = [];

                    foreach (range(1, $size) as $index) {
                        $expected["feed-$index.json"] = 'feed.json';

                        if ($registry === 'existing') {
                            $expected["foreign-$index.json"] = 'foreign.json';
                        }
                    }

                    ksort($expected, SORT_NATURAL);

                    for ($run = 0; $run <= $runs; $run++) {
                        $start  = hrtime(true);
                        $result = $next->invoke($service, $publication, $targets, $ownership);
                        $time   = (hrtime(true) - $start) / 1_000_000;

                        if ($result !== $expected) {
                            throw new RuntimeException('Ownership calculation produced an unexpected registry.');
                        }

                        if ($run > 0) {
                            $times[] = $time;
                        }
                    }

                    fputcsv(STDOUT, ['ownership', $label, $backend, $size, $registry, $runs, publicationScalingMedian($times)], escape: '');
                }
            }

            foreach ([64, 128, 256] as $size) {
                $times = ['fresh' => [], 'repeat' => [], 'shrinking' => []];

                for ($run = 0; $run <= $runs; $run++) {
                    $directory = (new TemporaryDirectory)->location($workspace->path())->create();
                    $adapter   = new LocalFilesystemAdapter($directory->path());

                    if ($remote) {
                        $adapter = new PublicationBenchmarkRemoteAdapter($adapter);
                    }

                    $storage = new FilesystemAdapter(new Flysystem($adapter), $adapter, ['root' => $directory->path()]);

                    try {
                        foreach (['fresh' => $size, 'repeat' => $size, 'shrinking' => intdiv($size, 2)] as $phase => $parts) {
                            $start = hrtime(true);

                            $service->publishTo($storage, 'feed.json', function (string $staging) use ($parts) {
                                $drafts = [];

                                foreach (range(1, $parts) as $index) {
                                    $draft = $staging . DIRECTORY_SEPARATOR . "part-$index";

                                    file_put_contents($draft, "part-$index");

                                    $drafts["feed-$index.json"] = $draft;
                                }

                                return $drafts;
                            });

                            $time = (hrtime(true) - $start) / 1_000_000;

                            if ($storage->get('feed-1.json') !== 'part-1' || $storage->exists("feed-$size.json") !== ($phase !== 'shrinking')) {
                                throw new RuntimeException('Publication produced unexpected split parts.');
                            }

                            if ($run > 0) {
                                $times[$phase][] = $time;
                            }
                        }
                    } finally {
                        $directory->delete();
                    }
                }

                foreach ($times as $phase => $samples) {
                    fputcsv(STDOUT, ['publication', $label, $backend, $size, $phase, $runs, publicationScalingMedian($samples)], escape: '');
                }
            }
        }
    }
} finally {
    $workspace->delete();
}
