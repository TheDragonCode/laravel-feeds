<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

pest()
    ->printer()
    ->compact();

pest()
    ->extend(TestCase::class)
    ->use(DatabaseTransactions::class)
    ->in('Feature');
