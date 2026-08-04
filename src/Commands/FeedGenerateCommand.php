<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Commands;

use DragonCode\LaravelFeed\Data\GenerationResultData;
use DragonCode\LaravelFeed\Exceptions\InvalidFeedArgumentException;
use DragonCode\LaravelFeed\Jobs\FeedJob;
use DragonCode\LaravelFeed\Queries\FeedQuery;
use DragonCode\LaravelFeed\Services\AgentDetectorService;
use DragonCode\LaravelFeed\Services\GeneratorService;
use Illuminate\Console\Command;
use Laravel\Prompts\Concerns\Colors;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

use function app;
use function config;
use function is_numeric;
use function json_encode;

#[AsCommand('feed:generate', 'Generate XML feeds')]
final class FeedGenerateCommand extends Command
{
    use Colors;

    public function handle(
        GeneratorService $generator,
        FeedQuery $query,
        AgentDetectorService $agentDetector,
    ): void {
        $feeds = $this->feedable($query);

        if ($agentDetector->isAgent()) {
            $this->performForAgent($generator, $feeds);

            return;
        }

        foreach ($feeds as $feed => $enabled) {
            if (! $enabled) {
                $this->components->twoColumnDetail($feed, $this->textYellow('SKIP'));

                continue;
            }

            if ($this->hasQueue()) {
                $this->performWithQueue($feed);

                continue;
            }

            $this->hasProgressBar()
                ? $this->performWithProgressBar($generator, $feed)
                : $this->performWithoutProgressBar($generator, $feed);
        }
    }

    protected function performForAgent(GeneratorService $generator, array $feeds): void
    {
        $results = [];

        foreach ($feeds as $feed => $enabled) {
            if (! $enabled) {
                $results[] = [
                    'class'  => $feed,
                    'status' => 'skipped',
                ];

                continue;
            }

            if ($this->hasQueue()) {
                FeedJob::dispatch($feed);

                $results[] = [
                    'class'  => $feed,
                    'status' => 'queued',
                ];

                continue;
            }

            $results[] = $this->generatedFeed($feed, $generator->feed(app($feed)));
        }

        $this->output->writeln(json_encode([
            'tool'   => 'feed:generate',
            'result' => 'success',
            'feeds'  => $results,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE), OutputInterface::OUTPUT_RAW);
    }

    protected function generatedFeed(string $feed, GenerationResultData $result): array
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

    protected function performWithQueue(string $feed): void
    {
        $this->components->twoColumnDetail($feed, $this->textGreen('QUEUED'));

        FeedJob::dispatch($feed);
    }

    protected function performWithProgressBar(GeneratorService $generator, string $feed): void
    {
        $this->components->info($feed);

        $generator->feed(app($feed), $this->output);
    }

    protected function performWithoutProgressBar(GeneratorService $generator, string $feed): void
    {
        $this->components->task($feed, fn () => $generator->feed(app($feed)));
    }

    protected function feedable(FeedQuery $feeds): array
    {
        if (! $id = $this->argument('feed')) {
            return $feeds->all()
                ->pluck('is_active', 'class')
                ->all();
        }

        if (! is_numeric($id)) {
            throw new InvalidFeedArgumentException($id);
        }

        $feed = $feeds->find((int) $id);

        return [$feed->class => true];
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

    protected function hasProgressBar(): bool
    {
        return config()->boolean('feeds.console.progress_bar');
    }

    protected function hasQueue(): bool
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
