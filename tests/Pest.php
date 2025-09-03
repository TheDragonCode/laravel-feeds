<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

pest()
    ->printer()
    ->compact();

pest()
    ->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature')
    ->beforeEach(function () {
        mockOperations();
        mockPaths();

        deleteOperations();
        deleteMigrations();
    })
    ->afterEach(function () {
        deleteOperations();
        deleteMigrations();
    });

pest()
    ->extend(TestCase::class)
    ->in('Unit')
    ->beforeEach(function () {
        Carbon::setTestNow('2025-09-03 01:50:24');

        mockOperations();
        mockPaths();

        deleteOperations();
        deleteMigrations();
    })
    ->afterEach(function () {
        deleteOperations();
        deleteMigrations();
    });
