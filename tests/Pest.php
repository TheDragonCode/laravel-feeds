<?php

use Illuminate\Foundation\Testing\DatabaseTruncation;

pest()
    ->printer()
    ->compact();

pest()
    ->extend(Tests\TestCase::class)
    ->use(DatabaseTruncation::class)
    ->in('Feature');
