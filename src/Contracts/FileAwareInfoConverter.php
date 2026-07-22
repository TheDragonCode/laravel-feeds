<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Contracts;

interface FileAwareInfoConverter
{
    public function infoForFile(array $info, bool $afterRoot, bool $hasItems): string;
}
