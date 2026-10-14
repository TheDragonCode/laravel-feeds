<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Services\FilesystemService;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemAdapter;
use League\Flysystem\DecoratedAdapter;
use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Spatie\TemporaryDirectory\TemporaryDirectory;

final class OwnershipKeyFilesystemService extends FilesystemService
{
    public ?string $remoteRoot = null;

    public bool $changingKeys = false;

    public function assertNormalizedOwnership(
        string $path,
        array $targets,
        array $ownership,
        Closure $pathKey,
    ): void {
        $this->assertOwnedTargets(
            $path,
            $targets,
            $ownership,
            static fn (string $target): bool => true,
            static fn (string $filename): string => "exports/$filename",
            $pathKey,
        );
    }

    public function nextNormalizedOwnership(string $path, array $targets, array $ownership): array
    {
        return $this->nextPublicationOwnership(
            $path,
            $targets,
            $ownership,
            static fn (string $filename): string => "exports/$filename",
            static fn (string $target): string => strtolower(str_replace('\\', '/', $target)),
        );
    }

    protected function pathKey(string $path): string
    {
        $key = parent::pathKey($path);

        if ($this->changingKeys && basename($path) === 'feed-1.json') {
            return (is_file($path) ? 'present:' : 'missing:') . $key;
        }

        return $key;
    }

    protected function storagePathKey(string $path): string
    {
        $key = parent::storagePathKey($path);

        if ($this->changingKeys && basename($path) === 'feed-1.json') {
            return (is_file($this->remoteRoot . '/' . $path) ? 'present:' : 'missing:') . $key;
        }

        return $key;
    }
}

final class OwnershipRemoteAdapter extends DecoratedAdapter {}

function ownershipTestDraft(string $staging, string $content): string
{
    $path = $staging . DIRECTORY_SEPARATOR . 'draft';

    file_put_contents($path, $content);

    return $path;
}

function ownershipTestRegistry(TemporaryDirectory $directory, array $owners): string
{
    $path = $directory->path('.laravel-feeds') . DIRECTORY_SEPARATOR . 'ownership.json';

    (new Filesystem)->ensureDirectoryExists(dirname($path));

    file_put_contents($path, json_encode([
        'format'  => 'dragon-code/laravel-feeds-ownership',
        'version' => 1,
        'owners'  => $owners,
    ], JSON_THROW_ON_ERROR));

    return $path;
}

test('matches normalized ownership and preserves target spelling in natural order', function () {
    $service   = new OwnershipKeyFilesystemService(new Filesystem);
    $path      = 'exports/catalog.json';
    $targets   = ['exports/CATALOG-10.json', 'exports/CATALOG-2.json', 'exports/CATALOG-1.json'];
    $ownership = [
        'catalog-1.json' => 'catalog.json',
        'catalog-3.json' => 'catalog.json',
        'unrelated.json' => 'unrelated.json',
    ];

    $service->assertNormalizedOwnership(
        $path,
        ['exports\\CATALOG-1.json'],
        $ownership,
        static fn (string $target): string => strtolower(str_replace('\\', '/', $target)),
    );

    expect($service->nextNormalizedOwnership($path, $targets, $ownership))->toBe([
        'CATALOG-1.json'  => 'catalog.json',
        'CATALOG-2.json'  => 'catalog.json',
        'CATALOG-10.json' => 'catalog.json',
        'unrelated.json'  => 'unrelated.json',
    ]);
});

test('keeps case-distinct storage targets independent', function () {
    $service = new OwnershipKeyFilesystemService(new Filesystem);

    expect(fn () => $service->assertNormalizedOwnership(
        'exports/catalog.json',
        ['exports/Catalog-1.json'],
        ['catalog-1.json' => 'catalog.json'],
        static fn (string $target): string => str_replace('\\', '/', $target),
    ))->toThrow(RuntimeException::class, 'Feed publication target is not owned: [exports/Catalog-1.json].');
});

test('republishes a local primary through its resolved directory alias', function () {
    $directory   = (new TemporaryDirectory)->create();
    $publication = $directory->path('feed.json');
    $filename    = DIRECTORY_SEPARATOR === '\\' ? 'FEED.json' : 'feed.json';
    $alias       = $directory->path() . DIRECTORY_SEPARATOR . '.' . DIRECTORY_SEPARATOR . $filename;
    $service     = new FilesystemService(new Filesystem);
    $registry    = ownershipTestRegistry($directory, ['feed.json' => 'feed.json']);

    file_put_contents($publication, 'old');

    try {
        $service->publish($alias, static fn (string $staging): array => [
            $alias => ownershipTestDraft($staging, 'new'),
        ]);

        expect(file_get_contents($publication))->toBe('new')
            ->and(json_decode(file_get_contents($registry), true, flags: JSON_THROW_ON_ERROR)['owners'])
            ->toBe([$filename => $filename]);
    } finally {
        $directory->delete();
    }
});

test('normalizes obsolete-part membership after installing the drafts', function (bool $remote) {
    $directory             = (new TemporaryDirectory)->create();
    $service               = new OwnershipKeyFilesystemService(new Filesystem);
    $service->changingKeys = true;
    $service->remoteRoot   = $directory->path();

    ownershipTestRegistry($directory, ['feed-1.json' => 'feed.json']);

    try {
        if ($remote) {
            $adapter = new OwnershipRemoteAdapter(new LocalFilesystemAdapter($directory->path()));
            $storage = new FilesystemAdapter(new Flysystem($adapter), $adapter, ['root' => 'remote-root']);

            $service->publishTo($storage, 'feed.json', static fn (string $staging): array => [
                'feed-1.json' => ownershipTestDraft($staging, 'new-part'),
            ]);
        } else {
            $service->publish($directory->path('feed.json'), static fn (string $staging): array => [
                $directory->path('feed-1.json') => ownershipTestDraft($staging, 'new-part'),
            ]);
        }

        expect(file_get_contents($directory->path('feed-1.json')))->toBe('new-part');
    } finally {
        $directory->delete();
    }
})->with(['local' => false, 'remote' => true]);

test('reloads ownership when the same filesystem service publishes again', function () {
    $directory   = (new TemporaryDirectory)->create();
    $publication = $directory->path('catalog-1.json');
    $service     = new FilesystemService(new Filesystem);

    try {
        $service->publish($publication, static fn (string $staging): array => [
            $publication => ownershipTestDraft($staging, 'independent'),
        ]);

        $registry = ownershipTestRegistry($directory, ['catalog-1.json' => 'catalog.json']);
        $contents = file_get_contents($registry);

        expect(fn () => $service->publish($publication, static fn (string $staging): array => [
            $publication => ownershipTestDraft($staging, 'overwrite'),
        ]))->toThrow(RuntimeException::class, "Feed publication target is not owned: [$publication].")
            ->and(file_get_contents($publication))->toBe('independent')
            ->and(file_get_contents($registry))->toBe($contents);
    } finally {
        $directory->delete();
    }
});
