<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Services;

use Laravel\AgentDetector\AgentDetector;

class AgentDetectorService
{
    public function isAgent(): bool
    {
        return AgentDetector::detect()->isAgent;
    }
}
