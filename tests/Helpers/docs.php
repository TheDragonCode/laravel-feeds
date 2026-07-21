<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Storage;
use Spatie\TemporaryDirectory\TemporaryDirectory;
use Symfony\Component\Process\Process;
use Workbench\App\Models\User;

use function Orchestra\Testbench\workbench_path;

function copyFeedFileToDoc(string $source, string $target, array $replaces = [], bool $cutFilename = true): void
{
    $sourceFile = workbench_path('app/Feeds/Docs/' . $source . '.php');
    $targetFile = docsGeneratedPath($target);

    $content = file_get_contents($sourceFile);

    if (! empty($replaces)) {
        $content = str_replace(
            array_keys($replaces),
            array_values($replaces),
            $content
        );
    }

    $content = str_replace([
        'Workbench\App\Feeds\Docs',
        'Workbench\App\\',
    ], [
        'App\Feeds',
        'App\\',
    ], $content);

    if ($cutFilename) {
        $content = preg_replace('/(\n\s+public\sfunction\sfilename\(\):\sstring\n\s+{\n\s+.*\n\s+})/', '', $content);
    }

    file_put_contents($targetFile, $content);
}

function createDocsWorkspace(): void
{
    deleteDocsWorkspace();

    $workspace = (new TemporaryDirectory)
        ->deleteWhenDestroyed()
        ->create();

    $GLOBALS['laravelFeedsDocsWorkspace'] = $workspace;

    docsDebug('Workspace created.', ['path' => $workspace->path()]);
}

function configureDocsWorkspace(): void
{
    $root = docsWorkspace()->path(
        implode(DIRECTORY_SEPARATOR, array_fill(0, 9, 'storage'))
    );

    Storage::set('public', Storage::build([
        'driver' => 'local',
        'root'   => $root,
        'throw'  => true,
    ]));

    docsDebug('Filesystem redirected.', ['root' => $root]);
}

function stabilizeDocsFixtures(): void
{
    stabilizeDocsFaker();

    foreach (User::query()->orderBy('id')->get() as $index => $user) {
        $number = $index + 1;

        $user->forceFill([
            'name'       => 'User ' . $number,
            'email'      => 'user' . $number . '@example.com',
            'created_at' => getDefaultDateTime(),
            'updated_at' => getDefaultDateTime(),
        ])->saveQuietly();
    }
}

function stabilizeDocsFaker(): void
{
    fake();

    mt_srand(168, MT_RAND_MT19937);
}

function finishDocsWorkspace(?bool $update = null): void
{
    if (! hasDocsWorkspace()) {
        return;
    }

    try {
        $generated = docsGeneratedDirectory();
        $tracked   = docsTrackedDirectory();

        formatDocsDirectory($generated);

        if ($update ?? shouldUpdateDocs()) {
            updateDocsDirectory($generated, $tracked);
        } else {
            compareDocsDirectory($generated, $tracked);
        }
    } finally {
        deleteDocsWorkspace();
    }
}

function deleteDocsWorkspace(): void
{
    if (! hasDocsWorkspace()) {
        return;
    }

    $workspace = docsWorkspace();
    $path      = $workspace->path();

    unset($GLOBALS['laravelFeedsDocsWorkspace']);

    if (! $workspace->delete()) {
        throw new RuntimeException("Unable to delete documentation workspace: [$path].");
    }

    docsDebug('Workspace deleted.', ['path' => $path]);
}

function hasDocsWorkspace(): bool
{
    return ($GLOBALS['laravelFeedsDocsWorkspace'] ?? null) instanceof TemporaryDirectory;
}

function docsWorkspace(): TemporaryDirectory
{
    $workspace = $GLOBALS['laravelFeedsDocsWorkspace'] ?? null;

    if (! $workspace instanceof TemporaryDirectory) {
        throw new RuntimeException('Documentation workspace has not been created.');
    }

    return $workspace;
}

function docsWorkspacePath(): string
{
    return docsWorkspace()->path();
}

function docsGeneratedDirectory(): string
{
    return docsWorkspace()->path(
        implode(DIRECTORY_SEPARATOR, ['docs', 'snippets'])
    );
}

function docsGeneratedPath(string $filename): string
{
    return docsWorkspace()->path(
        implode(DIRECTORY_SEPARATOR, ['docs', 'snippets', $filename])
    );
}

function docsTrackedDirectory(): string
{
    return implode(DIRECTORY_SEPARATOR, [dirname(__DIR__, 2), 'docs', 'snippets']);
}

function compareDocsDirectory(string $generated, string $tracked): void
{
    foreach (docsFiles($generated) as $file) {
        compareDocsSnippet(
            $file,
            $tracked . DIRECTORY_SEPARATOR . basename($file)
        );
    }
}

function compareDocsSnippet(string $generated, string $tracked): void
{
    $actual   = normalizeDocsSnippet(readDocsSnippet($generated));
    $expected = normalizeDocsSnippet(readDocsSnippet($tracked));

    if ($actual !== $expected) {
        docsDebug('Snippet mismatch.', [
            'generated' => $generated,
            'tracked'   => $tracked,
            'actual'    => $actual,
            'expected'  => $expected,
        ]);

        throw new RuntimeException(
            "Generated documentation snippet does not match [$tracked]. Run [composer test:update] to update it."
        );
    }

    docsDebug('Snippet matched.', [
        'generated' => $generated,
        'tracked'   => $tracked,
        'sha256'    => hash('sha256', $actual),
    ]);
}

function updateDocsDirectory(string $generated, string $tracked): void
{
    foreach (docsFiles($generated) as $file) {
        $target  = $tracked . DIRECTORY_SEPARATOR . basename($file);
        $content = normalizeDocsSnippet(readDocsSnippet($file));

        writeDocsSnippet($target, $content);

        docsDebug('Snippet updated.', [
            'generated' => $file,
            'tracked'   => $target,
            'sha256'    => hash('sha256', $content),
        ]);
    }
}

function formatDocsDirectory(string $directory): void
{
    $files = glob($directory . DIRECTORY_SEPARATOR . '*.php') ?: [];

    if ($files === []) {
        return;
    }

    sort($files, SORT_STRING);

    $root = dirname(__DIR__, 2);

    $process = new Process([
        PHP_BINARY,
        $root . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'pint',
        '--config=' . $root . DIRECTORY_SEPARATOR . 'pint.json',
        ...$files,
    ], $root);

    $process->setTimeout(null);

    docsDebug('Formatting started.', ['files' => $files]);

    $process->mustRun();

    docsDebug('Formatting finished.', ['files' => $files]);
}

function docsFiles(string $directory): array
{
    $files = array_values(
        array_filter(glob($directory . DIRECTORY_SEPARATOR . '*') ?: [], is_file(...))
    );

    sort($files, SORT_STRING);

    return $files;
}

function readDocsSnippet(string $path): string
{
    $content = @file_get_contents($path);

    if ($content === false) {
        throw new RuntimeException("Unable to read documentation snippet: [$path].");
    }

    return $content;
}

function writeDocsSnippet(string $path, string $content): void
{
    if (@file_put_contents($path, $content) === false) {
        throw new RuntimeException("Unable to write documentation snippet: [$path].");
    }
}

function normalizeDocsSnippet(string $content): string
{
    return str_replace(["\r\n", "\r"], "\n", $content);
}

function shouldUpdateDocs(): bool
{
    return filter_var(getenv('LARAVEL_FEEDS_UPDATE_DOCS') ?: false, FILTER_VALIDATE_BOOL);
}

function docsDebug(string $message, array $context = []): void
{
    $level = strtolower((string) getenv('LOG_LEVEL'));

    if ($level !== 'debug' && getenv('LARAVEL_FEEDS_DEBUG_DOCS') !== '1') {
        return;
    }

    $data = $context === []
        ? ''
        : ' ' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    fwrite(STDERR, '[FIX:168] ' . $message . $data . PHP_EOL);
}
