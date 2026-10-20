<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Services\AgentService;

test('detects an AI agent', function () {
    $previous = getenv('AI_AGENT');

    putenv('AI_AGENT=laravel-feeds-test');

    try {
        expect((new AgentService)->isAgent())->toBeTrue();
    } finally {
        $previous === false
            ? putenv('AI_AGENT')
            : putenv('AI_AGENT=' . $previous);
    }
});
