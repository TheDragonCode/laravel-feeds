<?php

declare(strict_types=1);

namespace Workbench\Database\Seeders;

use Illuminate\Database\Seeder;
use Workbench\App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()
            ->count(2)
            ->create();
    }
}
