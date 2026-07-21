<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()
    ->printer()
    ->compact();

pest()
    ->extend(TestCase::class)
    ->in('Benchmark');

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
        try {
            expect('end of snapshots')->toMatchSnapshot();
        } finally {
            try {
                finishDocsWorkspace();
            } finally {
                deleteOperations();
                deleteMigrations();
            }
        }
    });

pest()
    ->in('Feature/Docs')
    ->beforeEach(function () {
        createDocsWorkspace();
        configureDocsWorkspace();
        stabilizeDocsFixtures();
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
