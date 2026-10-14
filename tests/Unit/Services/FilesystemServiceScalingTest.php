<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Services\FilesystemService;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemAdapter;
use League\Flysystem\DecoratedAdapter;
use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Spatie\TemporaryDirectory\TemporaryDirectory;

final class ScalingFilesystemService extends FilesystemService
{
    public int $normalizations = 0;

    public function nextForScaling(array $targets, array $ownership): array
    {
        return $this->nextPublicationOwnership(
            'feeds/feed.json',
            $targets,
            $ownership,
            fn (string $filename) => 'feeds/' . $filename,
            fn (string $path) => $this->storagePathKey($path),
        );
    }

    public function assertForScaling(array $targets, array $ownership): void
    {
        $this->assertOwnedTargets(
            'feeds/feed.json',
            $targets,
            $ownership,
            fn (string $path) => false,
            fn (string $filename) => 'feeds/' . $filename,
            fn (string $path) => $this->storagePathKey($path),
        );
    }

    protected function pathKey(string $path): string
    {
        $this->normalizations++;

        return parent::pathKey($path);
    }

    protected function storagePathKey(string $path): string
    {
        $this->normalizations++;

        return parent::storagePathKey($path);
    }
}

final class ScalingRemoteAdapter extends DecoratedAdapter {}

function assertLinearNormalizations(array $counts): void
{
    foreach (array_slice($counts, 1) as $index => $count) {
        test()->assertLessThanOrEqual(
            $counts[$index] * 2 + 16,
            $count,
            json_encode($counts, JSON_THROW_ON_ERROR),
        );
    }
}

test('ownership membership normalization grows linearly', function (string $operation, bool $existing) {
    $counts = [];

    foreach ([16, 32, 64] as $size) {
        $service   = new ScalingFilesystemService(new Filesystem);
        $targets   = [];
        $ownership = [];

        foreach (range(1, $size) as $index) {
            $targets[] = "feeds/feed-$index.json";

            if ($existing) {
                $ownership["feed-$index.json"]    = 'feed.json';
                $ownership["foreign-$index.json"] = 'foreign.json';
            }
        }

        if ($operation === 'next') {
            $next = $service->nextForScaling($targets, $ownership);

            expect($next)->toHaveCount($existing ? $size * 2 : $size)
                ->and($next['feed-1.json'])->toBe('feed.json');

            if ($existing) {
                expect($next['foreign-1.json'])->toBe('foreign.json');
            }
        } else {
            $service->assertForScaling($targets, $ownership);
        }

        $counts[] = $service->normalizations;
    }

    assertLinearNormalizations($counts);
})->with([
    'next empty'    => ['next', false],
    'next existing' => ['next', true],
    'assert empty'  => ['assert', false],
    'assert owned'  => ['assert', true],
]);

test('complete publication membership normalization grows linearly', function (bool $remote, string $phase) {
    $counts = [];

    foreach ([16, 32, 64] as $size) {
        $directory = (new TemporaryDirectory)->create();
        $service   = new ScalingFilesystemService(new Filesystem);
        $adapter   = new LocalFilesystemAdapter($directory->path());

        if ($remote) {
            $adapter = new ScalingRemoteAdapter($adapter);
        }

        $storage = new FilesystemAdapter(new Flysystem($adapter), $adapter, ['root' => $directory->path()]);
        $publish = function (int $parts) use ($service, $storage): void {
            $service->publishTo($storage, 'feed.json', function (string $staging) use ($parts) {
                $drafts = [];

                foreach (range(1, $parts) as $index) {
                    $draft = $staging . DIRECTORY_SEPARATOR . "part-$index";

                    file_put_contents($draft, "part-$index");

                    $drafts["feed-$index.json"] = $draft;
                }

                return $drafts;
            });
        };

        try {
            if ($phase !== 'fresh') {
                $publish($size);
            }

            $service->normalizations = 0;
            $parts                   = $phase === 'shrinking' ? intdiv($size, 2) : $size;

            $publish($parts);

            $counts[] = $service->normalizations;

            expect($storage->get('feed-1.json'))->toBe('part-1')
                ->and($storage->exists("feed-$size.json"))->toBe($phase !== 'shrinking');
        } finally {
            $directory->delete();
        }
    }

    assertLinearNormalizations($counts);
})->with([
    'local fresh'      => [false, 'fresh'],
    'local repeat'     => [false, 'repeat'],
    'local shrinking'  => [false, 'shrinking'],
    'remote fresh'     => [true, 'fresh'],
    'remote repeat'    => [true, 'repeat'],
    'remote shrinking' => [true, 'shrinking'],
]);
