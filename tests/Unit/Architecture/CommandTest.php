<?php

declare(strict_types=1);

use Symfony\Component\Console\Attribute\AsCommand;

arch()->expect('DragonCode\LaravelFeed\Commands')
    ->toHaveSuffix('Command');

arch()->expect('DragonCode\LaravelFeed\Commands')
    ->toHaveAttribute(AsCommand::class);

arch()->expect('DragonCode\LaravelFeed\Commands')
    ->toBeUsedIn(['DragonCode\LaravelFeed']);
