<?php

declare(strict_types=1);

test('pins the Writerside Algolia publisher image to a reviewed digest', function () {
    $workflow = file_get_contents(dirname(__DIR__, 2) . '/.github/workflows/docs.yml');
    $workflow = str_replace("\r\n", "\n", $workflow);
    $tag      = 'registry.jetbrains.team/p/writerside/builder/algolia-publisher:2.0.32-3';
    $image    = $tag . '@sha256:fae5a7ab5f11f23b5c08014e507d11149a4e6844c7e3acd1957671428709ae9b';

    expect(substr_count($workflow, $image))->toBe(1)
        ->and($workflow)->not->toContain("            image: $tag\n");
});

test('scopes documentation deployment secrets to the Algolia deployment step', function () {
    $workflow = file_get_contents(dirname(__DIR__, 2) . '/.github/workflows/docs.yml');
    $workflow = str_replace("\r\n", "\n", $workflow);
    $marker   = '            -   name: Deploy to Algolia';
    $start    = strpos($workflow, $marker);

    expect($start)->not->toBeFalse();

    $step = substr($workflow, $start);
    $next = strpos($step, "\n            -   ", strlen($marker));

    if ($next !== false) {
        $step = substr($step, 0, $next);
    }

    expect($workflow)->not->toContain('COMPOSER_TOKEN');

    foreach (['ALGOLIA_APPLICATION_ID', 'ALGOLIA_KEY'] as $secret) {
        $expression = '${{ secrets.' . $secret . ' }}';

        expect(substr_count($workflow, $expression))->toBe(1)
            ->and($step)->toContain($expression);
    }
});
