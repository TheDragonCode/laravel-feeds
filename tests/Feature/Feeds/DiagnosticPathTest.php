<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Data\ElementData;
use DragonCode\LaravelFeed\Events\FeedFinishedEvent;
use DragonCode\LaravelFeed\Exceptions\FeedGenerationException;
use DragonCode\LaravelFeed\Exceptions\WriteFeedException;
use DragonCode\LaravelFeed\Feeds\FeedTarget;
use DragonCode\LaravelFeed\Feeds\Items\FeedItem;
use DragonCode\LaravelFeed\Helpers\ConverterHelper;
use DragonCode\LaravelFeed\Queries\FeedQuery;
use DragonCode\LaravelFeed\Services\FilesystemService;
use DragonCode\LaravelFeed\Services\GeneratorService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\Support\TargetedFeed;
use Workbench\App\Models\News;

class DiagnosticPathFeed extends TargetedFeed
{
    public array $pathStates = [];

    public string $state = 'before';

    public string $generation = 'first';

    public int $headerCalls = 0;

    public int $split = 0;

    public bool $statefulPath = false;

    protected string $storage = 'diagnostic-feeds';

    public function builder(): Builder
    {
        return News::query();
    }

    public function filename(): string
    {
        return "diagnostic-{$this->target()->key}-{$this->generation}.xml";
    }

    public function root(): ElementData
    {
        return new ElementData('items');
    }

    public function perFile(): int
    {
        return $this->split;
    }

    public function header(): string
    {
        $this->state = 'file-' . ++$this->headerCalls;

        return parent::header();
    }

    public function item(Model $model): FeedItem
    {
        $this->state = 'item-' . $model->getKey();

        return new class ($model) extends FeedItem {
            public function name(): string
            {
                return 'news';
            }

            public function toArray(): array
            {
                return ['id' => $this->model->getKey()];
            }
        };
    }

    public function path(int|string $suffix = ''): string
    {
        $this->pathStates[] = $this->state;

        return parent::path($suffix) . ($this->statefulPath ? '#' . $this->state : '');
    }
}

final class OtherDiagnosticPathFeed extends DiagnosticPathFeed
{
    public function filename(): string
    {
        return 'other-' . parent::filename();
    }
}

final class DiagnosticWriteFilesystem extends FilesystemService
{
    public array $itemPaths = [];

    public int $failOnItem = 0;

    public mixed $failedResource = null;

    public function append($resource, string $content, string $path): void
    {
        if (str_contains($content, '<news>')) {
            $this->itemPaths[] = $path;

            if (count($this->itemPaths) === $this->failOnItem) {
                $this->failedResource = $resource;

                fwrite($resource, substr($content, 0, 4));

                throw new WriteFeedException($path, 4, strlen($content));
            }
        }

        parent::append($resource, $content, $path);
    }
}

function diagnosticXml(array $ids): string
{
    return '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL . "<items>\n\n"
        . implode(PHP_EOL, array_map(static fn (int $id) => "<news><id>$id</id></news>", $ids))
        . "\n\n</items>\n";
}

function diagnosticGenerator(DiagnosticWriteFilesystem $filesystem): GeneratorService
{
    return new GeneratorService($filesystem, app(ConverterHelper::class), app(FeedQuery::class));
}

beforeEach(function () {
    Storage::fake('diagnostic-feeds');
    config()->set('feeds.pretty', false);
});

test('resolves one diagnostic path per non-empty generated file', function (int $count) {
    if ($count > 0) {
        News::query()->insert(array_map(static fn (int $id) => [
            'id'       => $id,
            'title'    => "News $id",
            'content'  => "Content $id",
            'category' => 'news',
        ], range(1, $count)));
    }

    $feed       = app(DiagnosticPathFeed::class)->forTarget(new FeedTarget('42'));
    $filesystem = new DiagnosticWriteFilesystem(new Filesystem);
    $path       = $feed->storage()->path($feed->storagePath());
    $result     = diagnosticGenerator($filesystem)->feed($feed);

    expect($feed->pathStates)
        ->toHaveCount($count === 0 ? 5 : 6)
        ->and($feed->headerCalls)->toBe(1)
        ->and($filesystem->itemPaths)->toBe(array_fill(0, $count, $path))
        ->and($result->paths)->toBe([$path])
        ->and($result->records)->toBe([$path => $count])
        ->and($feed->storage()->get($feed->storagePath()))
        ->toBe(diagnosticXml($count === 0 ? [] : range(1, $count)));
})->with([0, 1, 20]);

test('refreshes split diagnostics after file hooks without changing parts', function () {
    News::query()->insert(array_map(static fn (int $id) => [
        'id'       => $id,
        'title'    => "News $id",
        'content'  => "Content $id",
        'category' => 'news',
    ], range(1, 3)));

    $feed               = app(DiagnosticPathFeed::class)->forTarget(new FeedTarget('42'));
    $feed->split        = 2;
    $feed->statefulPath = true;
    $filesystem         = new DiagnosticWriteFilesystem(new Filesystem);
    $storage            = $feed->storage();
    $base               = $storage->path($feed->storagePath());
    $first              = $storage->path($feed->storagePath(1)) . '#item-2';
    $second             = $storage->path($feed->storagePath(2)) . '#file-2';
    $result             = diagnosticGenerator($filesystem)->feed($feed);

    expect($feed->pathStates)->toHaveCount(11)
        ->and($feed->headerCalls)->toBe(2)
        ->and($filesystem->itemPaths)->toBe([$base . '#file-1', $base . '#file-1', $base . '#file-2'])
        ->and($result->paths)->toBe([$first, $second])
        ->and($result->records)->toBe([$first => 2, $second => 1])
        ->and($storage->get($feed->storagePath(1)))->toBe(diagnosticXml([1, 2]))
        ->and($storage->get($feed->storagePath(2)))->toBe(diagnosticXml([3]))
        ->and($storage->exists($feed->storagePath()))->toBeFalse();
});

test('reports current item diagnostics and preserves publication after a write failure', function (
    int $split,
    int $failOnItem,
) {
    Event::fake();

    News::query()->insert(array_map(static fn (int $id) => [
        'id'       => $id,
        'title'    => "News $id",
        'content'  => "Content $id",
        'category' => 'news',
    ], range(1, 4)));

    $feed        = app(DiagnosticPathFeed::class)->forTarget(new FeedTarget('42'));
    $feed->split = $split;
    $filesystem  = new DiagnosticWriteFilesystem(new Filesystem);
    $generator   = diagnosticGenerator($filesystem);
    $storage     = $feed->storage();
    $result      = $generator->feed($feed);
    $published   = [];

    foreach ($result->paths as $path) {
        $published[$path] = file_get_contents($path);
    }

    $files                  = $storage->allFiles();
    $feed->state            = 'before';
    $feed->headerCalls      = 0;
    $feed->statefulPath     = true;
    $filesystem->itemPaths  = [];
    $filesystem->failOnItem = $failOnItem;
    $path                   = $storage->path($feed->storagePath()) . "#item-$failOnItem";
    $expectedBytes          = strlen(PHP_EOL . "<news><id>$failOnItem</id></news>");

    Event::fake();

    try {
        $generator->feed($feed);

        $this->fail('A feed write failure was expected.');
    } catch (FeedGenerationException $exception) {
        expect($exception->getPrevious())->toBeInstanceOf(WriteFeedException::class)
            ->and($exception->getPrevious()->getMessage())
            ->toBe("Failed to write to the feed: [$path]. Written [4] of [$expectedBytes] bytes.");
    }

    foreach ($published as $path => $content) {
        expect(file_get_contents($path))->toBe($content);
    }

    expect(is_resource($filesystem->failedResource))->toBeFalse()
        ->and($storage->allFiles())->toBe($files)
        ->and(glob($storage->path('') . DIRECTORY_SEPARATOR . '.feeds_staging_*'))->toBe([]);

    Event::assertNotDispatched(FeedFinishedEvent::class);
})->with([
    'active file' => [0, 2],
    'later part'  => [2, 4],
]);

test('isolates diagnostic paths across definitions targets and repeated generations', function () {
    News::query()->create(['title' => 'News', 'content' => 'Content', 'category' => 'news']);

    $definition = app(DiagnosticPathFeed::class);
    $first      = $definition->forTarget(new FeedTarget('42'));
    $second     = $definition->forTarget(new FeedTarget('81'));
    $other      = app(OtherDiagnosticPathFeed::class)->forTarget(new FeedTarget('42'));
    $filesystem = new DiagnosticWriteFilesystem(new Filesystem);
    $generator  = diagnosticGenerator($filesystem);
    $paths      = [];

    foreach ([$first, $second, $other, $first] as $index => $feed) {
        $feed->generation = (string) $index;
        $path             = $feed->storage()->path($feed->storagePath());
        $paths[]          = $path;
        $result           = $generator->feed($feed);

        expect($result->paths)->toBe([$path])
            ->and($result->records)->toBe([$path => 1])
            ->and($feed->storage()->get($feed->storagePath()))->toBe(diagnosticXml([1]));
    }

    expect($filesystem->itemPaths)->toBe($paths)
        ->and(array_unique($paths))->toHaveCount(4);
});
