<?php

declare(strict_types=1);

namespace Tests;

use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    use WithWorkbench;

    public bool $seed = true;
}
