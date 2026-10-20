<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Services;

use Laravel\AgentDetector\AgentDetector;

use function json_encode;

class AgentDetectorService
{
    private const int AGENT_JSON_FLAGS = JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE;

    public function isAgent(): bool
    {
        return AgentDetector::detect()->isAgent;
    }

    public function encode(mixed $content): string
    {
        return json_encode($content, self::AGENT_JSON_FLAGS) . PHP_EOL;
    }
}
