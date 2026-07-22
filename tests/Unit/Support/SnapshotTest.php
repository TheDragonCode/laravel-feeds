<?php

declare(strict_types=1);

use Tests\Support\Snapshot;

test('builds snapshot descriptions from dataset names only', function () {
    expect(Snapshot::description(
        '__pest_evaluable_export',
        ' with data set "dataset "true""'
    ))->toBe('export_with_data_set__dataset__true__');
});

test('builds the same snapshot path from windows and unix test paths', function (string $filename, string $testsPath) {
    expect(Snapshot::relativeTestPath($filename, $testsPath))
        ->toBe('Feature/Feeds/Defaults/EmptyTest');
})->with([
    'windows' => ['C:\\project\\tests\\Feature\\Feeds\\Defaults\\EmptyTest.php', 'C:\\project\\tests'],
    'unix'    => ['/project/tests/Feature/Feeds/Defaults/EmptyTest.php', '/project/tests'],
]);

test('fails the test gate for incomplete snapshots', function () {
    $configuration = new DOMDocument;
    $configuration->load(dirname(__DIR__, 3) . '/phpunit.xml');

    expect($configuration->documentElement?->getAttribute('failOnIncomplete'))
        ->toBe('true');
});

test('updates an existing snapshot in update mode', function () {
    $directory = dirname(__DIR__, 2) . '/.pest/snapshots/Unit/Support/SnapshotTest';
    $path      = $directory . '/updates_an_existing_snapshot_in_update_mode.snap';
    $arguments = $_SERVER['argv'] ?? null;

    if (! is_dir($directory)) {
        mkdir($directory, 0o755, true);
    }

    file_put_contents($path, 'previous snapshot');
    $_SERVER['argv'][] = '--update-snapshots';

    try {
        Snapshot::assertMatches('updated snapshot');

        expect(file_get_contents($path))->toBe('updated snapshot');
    } finally {
        if ($arguments === null) {
            unset($_SERVER['argv']);
        } else {
            $_SERVER['argv'] = $arguments;
        }

        if (is_file($path)) {
            unlink($path);
        }
    }
});
