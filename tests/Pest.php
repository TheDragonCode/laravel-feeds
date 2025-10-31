<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()
    ->printer()
    ->compact();

pest()
    ->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature')
    ->beforeEach(function () {
        setDefaultDateTime();

        mockOperations();
        mockPaths();

        deleteOperations();
        deleteMigrations();
    })
    ->afterEach(function () {
        expect('end of snapshots')->toMatchSnapshot();

        deleteOperations();
        deleteMigrations();
    });

pest()
    ->extend(TestCase::class)
    ->in('Unit')
    ->beforeEach(function () {
        setDefaultDateTime();

        mockOperations();
        mockPaths();

        deleteOperations();
        deleteMigrations();
    })
    ->afterEach(function () {
        deleteOperations();
        deleteMigrations();
    });
