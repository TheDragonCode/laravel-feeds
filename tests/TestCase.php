<?php

declare(strict_types=1);

namespace Tests;

use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Workbench\Database\Seeders\DatabaseSeeder;

class TestCase extends BaseTestCase
{
    use WithWorkbench;

    public string $seeder = DatabaseSeeder::class;
}
