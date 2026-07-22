<?php

declare(strict_types=1);

namespace Tests\Support;

use Pest\Support\Str;
use Pest\TestSuite;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class Snapshot
{
    private static array $counters = [];

    public static function assertMatches(string $value): void
    {
        $suite = TestSuite::getInstance();
        $test  = $suite->test;

        if (! $test instanceof TestCase) {
            throw new RuntimeException('Unable to resolve the current test case.');
        }

        $value = self::normalize($value);
        $path  = self::path($suite, $test);

        if (! is_file($path)) {
            self::save($path, $value);

            if (! self::isUpdating()) {
                Assert::fail(sprintf(
                    'Snapshot created at [%s].',
                    self::displayPath($path)
                ));
            }

            return;
        }

        $snapshot = file_get_contents($path);

        if ($snapshot === false) {
            throw new RuntimeException("Unable to read snapshot file: [$path].");
        }

        if (self::isUpdating()) {
            self::save($path, $value);

            return;
        }

        Assert::assertSame(
            self::normalizeLineEndings($snapshot),
            self::normalizeLineEndings($value),
            sprintf(
                'Failed asserting that the string value matches its snapshot (%s).',
                self::displayPath($path)
            )
        );
    }

    public static function description(string $testName, string $dataSetName): string
    {
        $description = str_replace('__pest_evaluable_', '', $testName);
        $dataSet     = str_replace('__pest_evaluable_', '', Str::evaluable($dataSetName));

        return str_replace(' ', '_', $description . $dataSet);
    }

    public static function relativeTestPath(string $filename, string $testsPath): string
    {
        $filename  = str_replace('\\', '/', $filename);
        $testsPath = rtrim(str_replace('\\', '/', $testsPath), '/');
        $prefix    = $testsPath . '/';

        if (! str_starts_with($filename, $prefix)) {
            throw new RuntimeException("Test file [$filename] is outside [$testsPath].");
        }

        $relative = substr($filename, strlen($prefix));
        $position = strrpos($relative, '.');

        if ($position === false) {
            throw new RuntimeException("Test file [$filename] has no extension.");
        }

        return substr($relative, 0, $position);
    }

    private static function path(TestSuite $suite, TestCase $test): string
    {
        $testsPath   = dirname(__DIR__);
        $relative    = self::relativeTestPath($suite->getFilename(), $testsPath);
        $description = self::description($test->name(), $test->dataSetAsString());
        $key         = $suite->getFilename() . '###' . $description;
        $counter     = (self::$counters[$key] ?? 0) + 1;

        self::$counters[$key] = $counter;

        if ($counter > 1) {
            $description .= '__' . $counter;
        }

        return implode(DIRECTORY_SEPARATOR, [
            $testsPath,
            '.pest',
            'snapshots',
            str_replace('/', DIRECTORY_SEPARATOR, $relative),
            $description . '.snap',
        ]);
    }

    private static function save(string $path, string $value): void
    {
        $directory = dirname($path);

        if (! is_dir($directory) && ! mkdir($directory, 0o755, true) && ! is_dir($directory)) {
            throw new RuntimeException("Unable to create snapshot directory: [$directory].");
        }

        if (file_put_contents($path, $value) === false) {
            throw new RuntimeException("Unable to write snapshot file: [$path].");
        }
    }

    private static function normalize(string $value): string
    {
        $normalized = preg_replace(
            pattern    : '/(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}.\d{6}Z)/',
            replacement: '2025-09-04T04:08:12.000000Z',
            subject    : $value
        );

        if ($normalized === null) {
            throw new RuntimeException('Unable to normalize snapshot value.');
        }

        return $normalized;
    }

    private static function normalizeLineEndings(string $value): string
    {
        return strtr($value, ["\r\n" => "\n", "\r" => "\n"]);
    }

    private static function isUpdating(): bool
    {
        return in_array('--update-snapshots', $_SERVER['argv'] ?? [], true);
    }

    private static function displayPath(string $path): string
    {
        $projectPath = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR;

        return str_replace('\\', '/', str_replace($projectPath, '', $path));
    }
}
