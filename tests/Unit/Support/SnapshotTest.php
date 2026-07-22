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
