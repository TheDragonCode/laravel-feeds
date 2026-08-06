<?php

declare(strict_types=1);

namespace Tests\Support;

class SplitTargetedFeed extends TargetedFeed
{
    public function perFile(): int
    {
        return 1;
    }
}
