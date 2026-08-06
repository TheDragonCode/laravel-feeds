<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Commands\FeedGenerateCommand;
use DragonCode\LaravelFeed\Data\GenerationResultData;
use DragonCode\LaravelFeed\Jobs\FeedJob;
use DragonCode\LaravelFeed\Models\Feed as FeedModel;
use DragonCode\LaravelFeed\Services\GeneratorService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\Console\Command\Command;
use Tests\Support\TargetedFeed;
use Workbench\App\Feeds\FullFeed;

use function Pest\Laravel\artisan;

beforeEach(function () {
    TargetedFeed::resetState();
});

function registerCommandTargetedFeed(): FeedModel
{
    return FeedModel::create([
        'class' => TargetedFeed::class,
        'title' => 'Targeted Feed',
    ]);
}

function mockCommandTargetGeneration(int $times, Closure $assertion): void
{
    $generator = mock(GeneratorService::class);
    $generator->shouldReceive('feed')
        ->times($times)
        ->withArgs($assertion)
        ->andReturn(new GenerationResultData([], []));

    app()->instance(GeneratorService::class, $generator);
}

test('streams all targets in synchronous mode', function () {
    $registration = registerCommandTargetedFeed();
    $processed    = [];

    mockCommandTargetGeneration(2, function (TargetedFeed $feed) use (&$processed) {
        $processed[] = $feed->target()->key;

        expect(TargetedFeed::$yieldedKeys)->toBe($processed);

        return true;
    });

    artisan(FeedGenerateCommand::class, [
        'feed' => (string) $registration->id,
    ])->assertSuccessful()->run();

    expect($processed)
        ->toBe(['42', '81'])
        ->and(TargetedFeed::$targetsCalls)
        ->toBe(1)
        ->and(TargetedFeed::$findTargetCalls)
        ->toBe([]);
});

test('uses direct lookup for an explicit target', function () {
    $registration = registerCommandTargetedFeed();
    $processed    = [];

    mockCommandTargetGeneration(1, function (TargetedFeed $feed) use (&$processed) {
        $processed[] = $feed->target()->key;

        return true;
    });

    artisan(FeedGenerateCommand::class, [
        'feed'     => (string) $registration->id,
        '--target' => ['42'],
    ])->assertSuccessful()->run();

    expect($processed)
        ->toBe(['42'])
        ->and(TargetedFeed::$findTargetCalls)
        ->toBe(['42'])
        ->and(TargetedFeed::$targetsCalls)
        ->toBe(0)
        ->and(TargetedFeed::$yieldedKeys)
        ->toBe([]);
});

test('deduplicates multiple explicit targets', function () {
    $registration = registerCommandTargetedFeed();
    $processed    = [];

    mockCommandTargetGeneration(2, function (TargetedFeed $feed) use (&$processed) {
        $processed[] = $feed->target()->key;

        return true;
    });

    artisan(FeedGenerateCommand::class, [
        'feed'     => (string) $registration->id,
        '--target' => ['42', '42', '81', '42'],
    ])->assertSuccessful()->run();

    expect($processed)
        ->toBe(['42', '81'])
        ->and(TargetedFeed::$findTargetCalls)
        ->toBe(['42', '81'])
        ->and(TargetedFeed::$targetsCalls)
        ->toBe(0);
});

test('rejects invalid target requests before generation', function () {
    $registration = registerCommandTargetedFeed();
    $ordinary     = findFeed(FullFeed::class);

    $generator = mock(GeneratorService::class);
    $generator->shouldNotReceive('feed');
    app()->instance(GeneratorService::class, $generator);

    expect(fn () => artisan(FeedGenerateCommand::class, [
        'feed'     => (string) $registration->id,
        '--target' => ['404'],
    ])->run())->toThrow(
        UnexpectedValueException::class,
        'Feed [' . TargetedFeed::class . '] target [404] not found.',
    );

    expect(fn () => artisan(FeedGenerateCommand::class, [
        'feed'     => (string) $ordinary->id,
        '--target' => ['42'],
    ])->run())->toThrow(
        InvalidArgumentException::class,
        'Feed [' . FullFeed::class . '] does not support target [42].',
    );

    expect(fn () => artisan(FeedGenerateCommand::class, [
        'feed'     => (string) $registration->id,
        '--target' => [''],
    ])->run())->toThrow(
        InvalidArgumentException::class,
        'Feed registration [' . $registration->id . '] target key [] cannot be empty.',
    );

    expect(fn () => artisan(FeedGenerateCommand::class, [
        '--target' => ['42'],
    ])->run())->toThrow(
        InvalidArgumentException::class,
        'Feed target [42] requires one feed registration ID; none was provided.',
    );
});

test('queues one job per target', function () {
    $registration = registerCommandTargetedFeed();

    config()->set([
        'feeds.queue.enabled'    => true,
        'feeds.queue.connection' => 'sync',
        'feeds.queue.name'       => 'feeds',
    ]);

    Queue::fake()->serializeAndRestore();

    artisan(FeedGenerateCommand::class, [
        'feed' => (string) $registration->id,
    ])->assertSuccessful()->run();

    Queue::assertPushed(FeedJob::class, 2);

    foreach (['42', '81'] as $key) {
        Queue::assertPushed(
            FeedJob::class,
            static fn (FeedJob $job) => $job->feedClass === TargetedFeed::class
                && $job->target?->key                   === $key
                && $job->target->parameters             === ['partner_id' => (int) $key]
                && $job->connection                     === 'sync'
                && $job->queue                          === 'feeds',
        );
    }

    expect(TargetedFeed::$targetsCalls)
        ->toBe(1)
        ->and(TargetedFeed::$yieldedKeys)
        ->toBe(['42', '81']);
});

test('returns one generated agent result per target', function () {
    $registration = registerCommandTargetedFeed();
    $processed    = [];

    mockAgent(true);

    mockCommandTargetGeneration(2, function (TargetedFeed $feed) use (&$processed) {
        $processed[] = $feed->target()->key;

        return true;
    });

    $status = Artisan::call(FeedGenerateCommand::class, [
        'feed' => (string) $registration->id,
    ]);
    $output = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

    expect($status)
        ->toBe(Command::SUCCESS)
        ->and($processed)
        ->toBe(['42', '81'])
        ->and($output)
        ->toBe([
            'tool'   => 'feed:generate',
            'result' => 'success',
            'feeds'  => [
                [
                    'class'  => TargetedFeed::class,
                    'target' => '42',
                    'status' => 'generated',
                    'files'  => [],
                ],
                [
                    'class'  => TargetedFeed::class,
                    'target' => '81',
                    'status' => 'generated',
                    'files'  => [],
                ],
            ],
        ]);
});

test('dispatches one synchronous agent job per target', function () {
    $registration = registerCommandTargetedFeed();

    mockAgent(true);

    config()->set([
        'feeds.queue.enabled'    => true,
        'feeds.queue.connection' => 'redis',
        'feeds.queue.name'       => 'feeds',
    ]);

    Bus::fake()->serializeAndRestore();

    $status = Artisan::call(FeedGenerateCommand::class, [
        'feed' => (string) $registration->id,
    ]);
    $output = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

    expect($status)
        ->toBe(Command::SUCCESS)
        ->and($output)
        ->toBe([
            'tool'   => 'feed:generate',
            'result' => 'success',
            'feeds'  => [
                [
                    'class'  => TargetedFeed::class,
                    'target' => '42',
                    'status' => 'queued',
                ],
                [
                    'class'  => TargetedFeed::class,
                    'target' => '81',
                    'status' => 'queued',
                ],
            ],
        ]);

    Bus::assertDispatchedSync(FeedJob::class, 2);

    foreach (['42', '81'] as $key) {
        Bus::assertDispatchedSync(
            FeedJob::class,
            static fn (FeedJob $job) => $job->feedClass === TargetedFeed::class
                && $job->target?->key                   === $key
                && $job->target->parameters             === ['partner_id' => (int) $key],
        );
    }
});
