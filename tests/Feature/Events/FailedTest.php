<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Commands\FeedGenerateCommand;
use DragonCode\LaravelFeed\Exceptions\FeedGenerationException;
use DragonCode\LaravelFeed\Feeds\Feed as BaseFeed;
use DragonCode\LaravelFeed\Feeds\Items\FeedItem;
use DragonCode\LaravelFeed\Models\Feed;
use DragonCode\LaravelFeed\Services\GeneratorService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Event;
use Spatie\TemporaryDirectory\TemporaryDirectory;
use Workbench\App\Feeds\FailedFeed;
use Workbench\App\Models\User;

use function Pest\Laravel\artisan;

final class FailedAfterDraftFeed extends BaseFeed
{
    public function builder(): Builder
    {
        return User::query();
    }

    public function filename(): string
    {
        return 'failed-after-draft.xml';
    }

    public function item(Model $model): FeedItem
    {
        return new FailedAfterDraftFeedItem($model);
    }
}

final class FailedAfterDraftFeedItem extends FeedItem
{
    public static int $calls = 0;

    public function toArray(): array
    {
        if (++self::$calls === 2) {
            throw new RuntimeException('Generation failed after opening a draft.');
        }

        return parent::toArray();
    }
}

test('failed', function () {
    Event::fake();

    $feed = Feed::create([
        'class' => FailedFeed::class,
        'title' => 'Failed',
    ]);

    artisan(FeedGenerateCommand::class, ['feed' => $feed->id])
        ->assertSuccessful()
        ->run();
})->throws(
    exception       : FeedGenerationException::class,
    exceptionMessage: 'Something went wrong while generating the feed.'
);

test('feed class link', function () {
    Event::fake();

    $feed = Feed::create([
        'class' => FailedFeed::class,
        'title' => 'Failed',
    ]);

    try {
        artisan(FeedGenerateCommand::class, ['feed' => $feed->id])
            ->assertSuccessful()
            ->run();
    } catch (FeedGenerationException $e) {
        expect($e->getMessage())->toContain('Something went wrong while generating the feed.');
        expect($e->getFeed())->toBe(FailedFeed::class);
    }
});

test('failed generation removes its draft and preserves another staging directory', function () {
    Event::fake();

    FailedAfterDraftFeedItem::$calls = 0;

    $feed       = app(FailedAfterDraftFeed::class);
    $filesystem = new Filesystem;
    $directory  = dirname($feed->path());

    $filesystem->ensureDirectoryExists($directory);

    $foreign = (new TemporaryDirectory)
        ->location($directory)
        ->name('.feeds_staging_foreign')
        ->create();
    $sentinel = $foreign->path('sentinel.txt');

    file_put_contents($sentinel, 'foreign');

    try {
        expect(fn () => app(GeneratorService::class)->feed($feed))
            ->toThrow(FeedGenerationException::class, 'Generation failed after opening a draft.');

        expect($feed->path())
            ->not->toBeFile()
            ->and(file_get_contents($sentinel))
            ->toBe('foreign')
            ->and(glob($directory . DIRECTORY_SEPARATOR . '.feeds_staging_*'))
            ->toBe([$foreign->path()]);
    } finally {
        $foreign->delete();
        $filesystem->delete($feed->path());
    }
});
