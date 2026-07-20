<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Enums\FeedFormatEnum;
use DragonCode\LaravelFeed\Events\FeedFinishedEvent;
use DragonCode\LaravelFeed\Exceptions\FeedGenerationException;
use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Feeds\Items\FeedItem;
use DragonCode\LaravelFeed\Models\Feed as FeedModel;
use DragonCode\LaravelFeed\Services\GeneratorService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Event;
use Workbench\App\Data\NewsFakeData;
use Workbench\App\Models\News;

final class PublicationSafetyFeed extends Feed
{
    public static int $perFile = 2;

    public static ?int $failOnId = null;

    protected FeedFormatEnum $format = FeedFormatEnum::JsonLines;

    public function builder(): Builder
    {
        return News::query();
    }

    public function filename(): string
    {
        return 'publication-safety.jsonl';
    }

    public function item(Model $model): FeedItem
    {
        return new PublicationSafetyFeedItem($model);
    }

    public function perFile(): int
    {
        return self::$perFile;
    }
}

final class PublicationSafetyFeedItem extends FeedItem
{
    public function toArray(): array
    {
        if ($this->model->getKey() === PublicationSafetyFeed::$failOnId) {
            throw new RuntimeException('Later split conversion failed.');
        }

        return parent::toArray();
    }
}

function publicationSafetySibling(Feed $feed, string $suffix): string
{
    $path      = $feed->path();
    $directory = dirname($path);
    $filename  = pathinfo($path, PATHINFO_FILENAME);
    $extension = pathinfo($path, PATHINFO_EXTENSION);

    return $directory . DIRECTORY_SEPARATOR . "$filename-$suffix.$extension";
}

function publicationSafetyArtifacts(Feed $feed): array
{
    $directory = dirname($feed->path()) . DIRECTORY_SEPARATOR;

    return [
        ...glob($directory . '.feeds_staging_*'),
        ...glob($directory . '.feeds_lock_*'),
    ];
}

function cleanupPublicationSafety(Feed $feed): void
{
    $filesystem = new Filesystem;

    $filesystem->delete($feed->path());

    foreach (range(1, 5) as $index) {
        $filesystem->delete($feed->path($index));
    }

    $filesystem->delete(publicationSafetySibling($feed, 'copy'));

    foreach (publicationSafetyArtifacts($feed) as $directory) {
        $filesystem->deleteDirectory($directory);
    }

    $filesystem->deleteDirectory(storage_path('framework/cache/laravel-feeds'));
}

beforeEach(function () {
    PublicationSafetyFeed::$perFile  = 2;
    PublicationSafetyFeed::$failOnId = null;

    cleanupPublicationSafety(app(PublicationSafetyFeed::class));
});

afterEach(function () {
    cleanupPublicationSafety(app(PublicationSafetyFeed::class));
});

test('keeps every published part unchanged when a later split conversion fails', function () {
    Event::fake();

    createNews(...NewsFakeData::toArray());

    $feed = app(PublicationSafetyFeed::class);

    $published = [
        $feed->path(1) => 'old-first',
        $feed->path(2) => 'old-second',
        $feed->path(3) => 'old-third',
    ];

    foreach ($published as $path => $content) {
        file_put_contents($path, $content);
    }

    $record = FeedModel::create([
        'class' => PublicationSafetyFeed::class,
        'title' => 'Publication Safety',
    ]);

    PublicationSafetyFeed::$failOnId = News::query()->orderBy('id')->value('id') + 2;

    expect(fn () => app(GeneratorService::class)->feed($feed))
        ->toThrow(FeedGenerationException::class, 'Later split conversion failed.');

    foreach ($published as $path => $content) {
        expect(file_get_contents($path))->toBe($content);
    }

    expect($record->refresh()->last_activity)
        ->toBeNull()
        ->and(publicationSafetyArtifacts($feed))
        ->toBe([]);

    Event::assertNotDispatched(FeedFinishedEvent::class);
});

test('publishes one part and removes only obsolete numeric parts', function () {
    Event::fake();

    createNews(...NewsFakeData::toArray());

    $feed = app(PublicationSafetyFeed::class);

    FeedModel::create([
        'class' => PublicationSafetyFeed::class,
        'title' => 'Publication Safety',
    ]);

    PublicationSafetyFeed::$perFile = 1;

    app(GeneratorService::class)->feed($feed);

    expect($feed->path(1))
        ->toBeFile()
        ->and($feed->path(2))
        ->toBeFile()
        ->and($feed->path(3))
        ->toBeFile();

    $unrelated = publicationSafetySibling($feed, 'copy');

    file_put_contents($unrelated, 'unrelated');

    News::query()->where('id', '>', News::query()->min('id'))->delete();

    PublicationSafetyFeed::$perFile = 0;

    app(GeneratorService::class)->feed($feed);

    expect($feed->path())
        ->toBeFile()
        ->and($feed->path(1))
        ->not->toBeFile()
        ->and($feed->path(2))
        ->not->toBeFile()
        ->and($feed->path(3))
        ->not->toBeFile()
        ->and(file_get_contents($unrelated))
        ->toBe('unrelated')
        ->and(publicationSafetyArtifacts($feed))
        ->toBe([]);
});
