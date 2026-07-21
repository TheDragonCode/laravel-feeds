<?php

declare(strict_types=1);

use Spatie\TemporaryDirectory\TemporaryDirectory;

test('compares generated snippets with normalized line endings without rewriting tracked files', function () {
    $directory = (new TemporaryDirectory)->create();
    $generated = $directory->path(implode(DIRECTORY_SEPARATOR, ['generated', 'example.txt']));
    $tracked   = $directory->path(implode(DIRECTORY_SEPARATOR, ['tracked', 'example.txt']));

    file_put_contents($generated, "first\r\nsecond\r\n");
    file_put_contents($tracked, "first\nsecond\n");

    try {
        compareDocsSnippet($generated, $tracked);

        expect(file_get_contents($tracked))->toBe("first\nsecond\n");
    } finally {
        $directory->delete();
    }
});

test('keeps tracked snippets unchanged when generated content differs', function () {
    $directory = (new TemporaryDirectory)->create();
    $generated = $directory->path(implode(DIRECTORY_SEPARATOR, ['generated', 'example.txt']));
    $tracked   = $directory->path(implode(DIRECTORY_SEPARATOR, ['tracked', 'example.txt']));

    file_put_contents($generated, "different\r\n");
    file_put_contents($tracked, "tracked\n");

    try {
        expect(fn () => compareDocsSnippet($generated, $tracked))
            ->toThrow(RuntimeException::class);

        expect(file_get_contents($tracked))->toBe("tracked\n");
    } finally {
        $directory->delete();
    }
});

test('writes documentation updates with LF line endings', function () {
    $directory = (new TemporaryDirectory)->create();
    $generated = $directory->path(implode(DIRECTORY_SEPARATOR, ['generated', 'example.txt']));
    $tracked   = $directory->path(implode(DIRECTORY_SEPARATOR, ['tracked', 'example.txt']));

    file_put_contents($generated, "first\r\nsecond\r\n");

    try {
        updateDocsDirectory(dirname($generated), dirname($tracked));

        expect(file_get_contents($tracked))->toBe("first\nsecond\n");
    } finally {
        $directory->delete();
    }
});

test('deletes the documentation workspace when comparison fails', function () {
    createDocsWorkspace();

    $workspace = docsWorkspacePath();

    file_put_contents(
        docsGeneratedPath('advanced-element-root.xml'),
        'different'
    );

    try {
        expect(fn () => finishDocsWorkspace(false))
            ->toThrow(RuntimeException::class);

        expect($workspace)->not->toBeDirectory();
    } finally {
        deleteDocsWorkspace();
    }
});
