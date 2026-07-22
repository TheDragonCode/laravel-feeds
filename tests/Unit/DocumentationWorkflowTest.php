<?php

declare(strict_types=1);

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
