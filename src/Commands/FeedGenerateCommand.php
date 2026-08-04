<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Commands;

use DragonCode\LaravelFeed\Data\GenerationResultData;
use DragonCode\LaravelFeed\Exceptions\InvalidFeedArgumentException;
use DragonCode\LaravelFeed\Jobs\FeedJob;
use DragonCode\LaravelFeed\Models\Feed;
use DragonCode\LaravelFeed\Queries\FeedQuery;
use DragonCode\LaravelFeed\Services\AgentDetectorService;
use DragonCode\LaravelFeed\Services\GeneratorService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Laravel\Prompts\Concerns\Colors;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

use function app;
use function config;
use function filter_var;
use function is_int;
use function is_string;
use function json_encode;
use function preg_match;

#[AsCommand('feed:generate', 'Generate XML feeds')]
final class FeedGenerateCommand extends Command
{
    use Colors;

    private const AGENT_JSON_FLAGS = JSON_THROW_ON_ERROR
        | JSON_UNESCAPED_SLASHES
        | JSON_INVALID_UTF8_SUBSTITUTE;

    public function handle(
        GeneratorService $generator,
        FeedQuery $query,
        AgentDetectorService $agentDetector,
    ): int {
        $feedId        = $this->feedId();
        $feeds         = $this->selectedFeeds($query, $feedId);
        $isTargetedRun = $feedId !== null;
        $usesQueue     = $this->usesQueue();

        if ($agentDetector->isAgent()) {
            $this->runForAgent($generator, $feeds, $isTargetedRun, $usesQueue);
        } else {
            $this->runForConsole(
                $generator,
                $feeds,
                $isTargetedRun,
                $usesQueue,
                $this->usesProgressBar()
            );
        }

        return self::SUCCESS;
    }

    private function runForConsole(
        GeneratorService $generator,
        Collection $feeds,
        bool $isTargetedRun,
        bool $usesQueue,
        bool $usesProgressBar,
    ): void {
        foreach ($feeds as $feed) {
            if (! $isTargetedRun && ! $feed->is_active) {
                $this->showSkippedFeed($feed->class);

                continue;
            }

            if ($usesQueue) {
                $this->dispatchFeed($feed->class);

                continue;
            }

            $this->generateFeed($generator, $feed->class, $usesProgressBar);
        }
    }

    private function runForAgent(
        GeneratorService $generator,
        Collection $feeds,
        bool $isTargetedRun,
        bool $usesQueue,
    ): void {
        $results = [];

        foreach ($feeds as $feed) {
            $results[] = $this->runFeedForAgent($generator, $feed, $isTargetedRun, $usesQueue);
        }

        $this->writeAgentOutput($results);
    }

    private function runFeedForAgent(
        GeneratorService $generator,
        Feed $feed,
        bool $isTargetedRun,
        bool $usesQueue,
    ): array {
        if (! $isTargetedRun && ! $feed->is_active) {
            return [
                'class'  => $feed->class,
                'status' => 'skipped',
            ];
        }

        if ($usesQueue) {
            FeedJob::dispatchSync($feed->class);

            return [
                'class'  => $feed->class,
                'status' => 'queued',
            ];
        }

        return $this->generatedAgentResult($feed->class, $generator->feed(app($feed->class)));
    }

    private function writeAgentOutput(array $feeds): void
    {
        $this->output->writeln(json_encode([
            'tool'   => 'feed:generate',
            'result' => 'success',
            'feeds'  => $feeds,
        ], self::AGENT_JSON_FLAGS), OutputInterface::OUTPUT_RAW);
    }

    private function generatedAgentResult(string $feed, GenerationResultData $result): array
    {
        $files = [];

        foreach ($result->records as $path => $records) {
            $files[] = [
                'path'    => $path,
                'records' => $records,
            ];
        }

        return [
            'class'  => $feed,
            'status' => 'generated',
            'files'  => $files,
        ];
    }

    private function showSkippedFeed(string $feed): void
    {
        $this->components->twoColumnDetail($feed, $this->textYellow('SKIP'));
    }

    private function dispatchFeed(string $feed): void
    {
        $this->components->twoColumnDetail($feed, $this->textGreen('QUEUED'));

        FeedJob::dispatch($feed);
    }

    private function generateFeed(GeneratorService $generator, string $feed, bool $usesProgressBar): void
    {
        if ($usesProgressBar) {
            $this->components->info($feed);

            $generator->feed(app($feed), $this->output);

            return;
        }

        $this->components->task($feed, fn () => $generator->feed(app($feed)));
    }

    private function selectedFeeds(FeedQuery $query, ?int $feedId): Collection
    {
        if ($feedId === null) {
            return $query->all()->get();
        }

        return new Collection([$query->find($feedId)]);
    }

    private function feedId(): ?int
    {
        $value = $this->argument('feed');

        if ($value === null) {
            return null;
        }

        if (is_int($value)) {
            if ($value < 1) {
                throw new InvalidFeedArgumentException($value);
            }

            return $value;
        }

        if (! is_string($value) || preg_match('/^[1-9][0-9]*$/D', $value) !== 1) {
            throw new InvalidFeedArgumentException($value);
        }

        $id = filter_var($value, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        if ($id === false) {
            throw new InvalidFeedArgumentException($value);
        }

        return $id;
    }

    private function textYellow(string $message): string
    {
        if ($this->option('no-ansi')) {
            // @codeCoverageIgnoreStart
            return $message;
            // @codeCoverageIgnoreEnd
        }

        return $this->yellow($message);
    }

    private function textGreen(string $message): string
    {
        if ($this->option('no-ansi')) {
            // @codeCoverageIgnoreStart
            return $message;
            // @codeCoverageIgnoreEnd
        }

        return $this->green($message);
    }

    private function usesProgressBar(): bool
    {
        return config()->boolean('feeds.console.progress_bar');
    }

    private function usesQueue(): bool
    {
        return config()->boolean('feeds.queue.enabled');
    }

    protected function getArguments(): array
    {
        return [
            ['feed', InputArgument::OPTIONAL, 'The Feed ID for generation (from the database)'],
        ];
    }
}
