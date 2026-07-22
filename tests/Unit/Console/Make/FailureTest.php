<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Commands\FeedMakeCommand;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command;

use function Pest\Laravel\artisan;

test('stops scaffolding when the feed already exists', function () {
    $filesystem = app(Filesystem::class);
    $paths      = [
        feedPath('ExistingScaffoldFeed'),
        feedPath('Items/ExistingScaffoldFeedItem'),
        feedPath('Info/ExistingScaffoldFeedInfo'),
    ];
    $operationPath = (string) config('deploy-operations.path');

    $filesystem->ensureDirectoryExists(dirname($paths[0]));
    $filesystem->put($paths[0], '<?php');
    $filesystem->deleteDirectory($operationPath);

    try {
        $status = artisan(FeedMakeCommand::class, [
            'name'   => 'ExistingScaffold',
            '--item' => true,
            '--info' => true,
        ])
            ->expectsOutputToContain('Feed already exists.')
            ->run();

        expect([
            'status'            => $status,
            'item_created'      => $filesystem->exists($paths[1]),
            'info_created'      => $filesystem->exists($paths[2]),
            'operation_created' => $filesystem->isDirectory($operationPath),
        ])->toBe([
            'status'            => Command::FAILURE,
            'item_created'      => false,
            'info_created'      => false,
            'operation_created' => false,
        ]);
    } finally {
        $filesystem->delete($paths);
        $filesystem->deleteDirectory($operationPath);
    }
});

test('stops scaffolding when the feed name is reserved', function () {
    mockOperations(false);

    $filesystem = app(Filesystem::class);
    $paths      = [
        feedPath('ClassFeed'),
        feedPath('Items/ClassFeedItem'),
        feedPath('Info/ClassFeedInfo'),
    ];
    $migrationPath = database_path('migrations');

    $filesystem->deleteDirectory($migrationPath);

    try {
        $status = artisan(FeedMakeCommand::class, [
            'name'   => 'class',
            '--item' => true,
            '--info' => true,
        ])
            ->expectsOutputToContain('The name "class" is reserved by PHP.')
            ->run();

        expect([
            'status'            => $status,
            'feed_created'      => $filesystem->exists($paths[0]),
            'item_created'      => $filesystem->exists($paths[1]),
            'info_created'      => $filesystem->exists($paths[2]),
            'migration_created' => $filesystem->isDirectory($migrationPath),
        ])->toBe([
            'status'            => Command::FAILURE,
            'feed_created'      => false,
            'item_created'      => false,
            'info_created'      => false,
            'migration_created' => false,
        ]);
    } finally {
        $filesystem->delete($paths);
        $filesystem->deleteDirectory($migrationPath);
    }
});
