<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Commands;

use DragonCode\LaravelFeed\Contracts\HasFeedTargets;
use DragonCode\LaravelFeed\Data\GenerationResultData;
use DragonCode\LaravelFeed\Exceptions\InvalidFeedArgumentException;
use DragonCode\LaravelFeed\Feeds\Feed as FeedDefinition;
use DragonCode\LaravelFeed\Feeds\FeedTarget;
use DragonCode\LaravelFeed\Jobs\FeedJob;
use DragonCode\LaravelFeed\Models\Feed;
use DragonCode\LaravelFeed\Queries\FeedQuery;
use DragonCode\LaravelFeed\Services\AgentDetectorService;
use DragonCode\LaravelFeed\Services\GeneratorService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;
use Laravel\Prompts\Concerns\Colors;
use RuntimeException;
use SplFileObject;
use SplTempFileObject;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use UnexpectedValueException;

use function app;
use function array_unique;
use function array_values;
use function config;
use function filter_var;
use function get_class;
use function is_int;
use function is_string;
use function json_encode;
use function preg_match;
use function sprintf;
use function trim;

#[AsCommand('feed:generate', 'Generate XML feeds')]
final class FeedGenerateCommand extends Command
{
    use Colors;

    private const AGENT_JSON_FLAGS = JSON_THROW_ON_ERROR
        | JSON_UNESCAPED_SLASHES
        | JSON_INVALID_UTF8_SUBSTITUTE;

    public function handle(GeneratorService $generator, FeedQuery $query, AgentDetectorService $agentDetector): int
    {
        $feedId          = $this->feedId();
        $targetKeys      = $this->targetKeys($feedId);
        $feeds           = $this->selectedFeeds($query, $feedId);
        $isSpecifiedFeed = $feedId !== null;
        $usesQueue       = $this->usesQueue();

        $agentDetector->isAgent()
            ? $this->runForAgent($generator, $feeds, $isSpecifiedFeed, $usesQueue, $targetKeys)
            : $this->runForConsole(
                $generator,
                $feeds,
                $isSpecifiedFeed,
                $usesQueue,
                $this->usesProgressBar(),
                $targetKeys,
            );

        return self::SUCCESS;
    }

    protected function getArguments(): array
    {
        return [
            ['feed', InputArgument::OPTIONAL, 'The Feed ID for generation (from the database)'],
        ];
    }

    protected function getOptions(): array
    {
        return [
            [
                'target',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Generate only the selected feed targets',
            ],
        ];
    }

    protected function runForConsole(
        GeneratorService $generator,
        Collection $feeds,
        bool $isSpecifiedFeed,
        bool $usesQueue,
        bool $usesProgressBar,
        array $targetKeys,
    ): void {
        foreach ($feeds as $feed) {
            if (! $isSpecifiedFeed && ! $feed->is_active) {
                $this->showSkippedFeed($feed->class);

                continue;
            }

            /** @var FeedDefinition $definition */
            $definition = app($feed->class);

            foreach ($this->executionTargets($definition, $targetKeys) as $target) {
                if ($usesQueue) {
                    $this->dispatchFeed($feed->class, $target);

                    continue;
                }

                $this->generateFeed($generator, $definition, $target, $usesProgressBar);
            }
        }
    }

    protected function runForAgent(
        GeneratorService $generator,
        Collection $feeds,
        bool $isSpecifiedFeed,
        bool $usesQueue,
        array $targetKeys,
    ): void {
        $results = new SplTempFileObject;

        foreach ($feeds as $feed) {
            foreach ($this->runFeedForAgent($generator, $feed, $isSpecifiedFeed, $usesQueue, $targetKeys) as $result) {
                $encoded = json_encode($result, self::AGENT_JSON_FLAGS) . PHP_EOL;

                if ($results->fwrite($encoded) === false) {
                    throw new RuntimeException('Unable to buffer agent output.');
                }
            }
        }

        $this->writeAgentOutput($results);
    }

    /** @return iterable<array<string, mixed>> */
    protected function runFeedForAgent(
        GeneratorService $generator,
        Feed $feed,
        bool $isSpecifiedFeed,
        bool $usesQueue,
        array $targetKeys,
    ): iterable {
        if (! $isSpecifiedFeed && ! $feed->is_active) {
            yield [
                'class'  => $feed->class,
                'status' => 'skipped',
            ];

            return;
        }

        /** @var FeedDefinition $definition */
        $definition = app($feed->class);

        foreach ($this->executionTargets($definition, $targetKeys) as $target) {
            if ($usesQueue) {
                FeedJob::dispatchSync($feed->class, $target);

                $result = ['class' => $feed->class];

                if ($target !== null) {
                    $result['target'] = $target->key;
                }

                $result['status'] = 'queued';

                yield $result;

                continue;
            }

            $execution = $target === null
                ? $definition
                : $definition->forTarget($target);

            yield $this->generatedAgentResult(
                $feed->class,
                $generator->feed($execution),
                $target,
            );
        }
    }

    protected function writeAgentOutput(SplTempFileObject $feeds): void
    {
        $this->output->write(
            '{"tool":"feed:generate","result":"success","feeds":[',
            false,
            OutputInterface::OUTPUT_RAW,
        );

        $feeds->setFlags(SplFileObject::DROP_NEW_LINE | SplFileObject::READ_AHEAD | SplFileObject::SKIP_EMPTY);

        $first = true;

        foreach ($feeds as $feed) {
            $this->output->write(
                ($first ? '' : ',') . $feed,
                false,
                OutputInterface::OUTPUT_RAW,
            );

            $first = false;
        }

        $this->output->writeln(']}', OutputInterface::OUTPUT_RAW);
    }

    protected function generatedAgentResult(
        string $feed,
        GenerationResultData $result,
        ?FeedTarget $target = null,
    ): array {
        $files = [];

        foreach ($result->records as $path => $records) {
            $files[] = [
                'path'    => $path,
                'records' => $records,
            ];
        }

        $response = ['class' => $feed];

        if ($target !== null) {
            $response['target'] = $target->key;
        }

        $response['status'] = 'generated';
        $response['files']  = $files;

        return $response;
    }

    protected function showSkippedFeed(string $feed): void
    {
        $this->components->twoColumnDetail($feed, $this->textYellow('SKIP'));
    }

    protected function dispatchFeed(string $feed, ?FeedTarget $target): void
    {
        $this->components->twoColumnDetail(
            $this->executionName($feed, $target),
            $this->textGreen('QUEUED'),
        );

        FeedJob::dispatch($feed, $target);
    }

    protected function generateFeed(
        GeneratorService $generator,
        FeedDefinition $feed,
        ?FeedTarget $target,
        bool $usesProgressBar,
    ): void {
        $execution = $target === null
            ? $feed
            : $feed->forTarget($target);
        $name = $this->executionName(get_class($feed), $target);

        if ($usesProgressBar) {
            $this->components->info($name);

            $generator->feed($execution, $this->output);

            return;
        }

        $this->components->task($name, fn () => $generator->feed($execution));
    }

    /** @return iterable<FeedTarget|null> */
    protected function executionTargets(FeedDefinition $feed, array $targetKeys): iterable
    {
        if (! $feed instanceof HasFeedTargets) {
            if ($targetKeys !== []) {
                throw new InvalidArgumentException(sprintf(
                    'Feed [%s] does not support target [%s].',
                    get_class($feed),
                    $targetKeys[0],
                ));
            }

            yield null;

            return;
        }

        if ($targetKeys === []) {
            yield from $feed->targets();

            return;
        }

        $targets = [];

        foreach ($targetKeys as $key) {
            $target = $feed->findTarget($key);

            if ($target === null) {
                throw new UnexpectedValueException(sprintf(
                    'Feed [%s] target [%s] not found.',
                    get_class($feed),
                    $key,
                ));
            }

            $targets[] = $target;
        }

        yield from $targets;
    }

    protected function executionName(string $feed, ?FeedTarget $target): string
    {
        return $target === null
            ? $feed
            : sprintf('%s [target: %s]', $feed, $target->key);
    }

    protected function selectedFeeds(FeedQuery $query, ?int $feedId): Collection
    {
        if ($feedId === null) {
            return $query->all()->get();
        }

        return new Collection([$query->find($feedId)]);
    }

    /** @return list<string> */
    protected function targetKeys(?int $feedId): array
    {
        /** @var array<int, string|null> $values */
        $values = $this->option('target');
        $keys   = [];

        foreach ($values as $key) {
            if ($key === null || trim($key) === '') {
                throw new InvalidArgumentException(sprintf(
                    'Feed registration [%s] target key [%s] cannot be empty.',
                    $feedId ?? 'unspecified',
                    $key    ?? '',
                ));
            }

            $keys[] = $key;
        }

        $keys = array_values(array_unique($keys, SORT_STRING));

        if ($keys !== [] && $feedId === null) {
            throw new InvalidArgumentException(sprintf(
                'Feed target [%s] requires one feed registration ID; none was provided.',
                $keys[0],
            ));
        }

        return $keys;
    }

    protected function feedId(): ?int
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

    protected function textYellow(string $message): string
    {
        if ($this->option('no-ansi')) {
            // @codeCoverageIgnoreStart
            return $message;
            // @codeCoverageIgnoreEnd
        }

        return $this->yellow($message);
    }

    protected function textGreen(string $message): string
    {
        if ($this->option('no-ansi')) {
            // @codeCoverageIgnoreStart
            return $message;
            // @codeCoverageIgnoreEnd
        }

        return $this->green($message);
    }

    protected function usesProgressBar(): bool
    {
        return config()->boolean('feeds.console.progress_bar');
    }

    protected function usesQueue(): bool
    {
        return config()->boolean('feeds.queue.enabled');
    }
}
