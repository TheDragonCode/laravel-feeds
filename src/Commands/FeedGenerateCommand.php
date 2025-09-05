<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Commands;

use DragonCode\LaravelFeed\Exceptions\InvalidFeedArgumentException;
use DragonCode\LaravelFeed\Queries\FeedQuery;
use DragonCode\LaravelFeed\Services\GeneratorService;
use Illuminate\Console\Command;
use Laravel\Prompts\Concerns\Colors;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;

use function app;
use function config;
use function is_numeric;

#[AsCommand('feed:generate', 'Generate XML feeds')]
class FeedGenerateCommand extends Command
{
    use Colors;

    public function handle(GeneratorService $generator, FeedQuery $query): void
    {
        foreach ($this->feedable($query) as $feed => $enabled) {
            if (! $enabled) {
                $this->components->twoColumnDetail($feed, $this->messageYellow('SKIP'));

                continue;
            }

            $this->hasProgressBar()
                ? $this->performWithProgressBar($generator, $feed)
                : $this->performWithoutProgressBar($generator, $feed);
        }
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

    protected function messageYellow(string $message): string
    {
        if ($this->option('no-ansi')) {
            // @codeCoverageIgnoreStart
            return $message;
            // @codeCoverageIgnoreEnd
        }

        return $this->yellow($message);
    }

    protected function hasProgressBar(): bool
    {
        return config()?->boolean('feeds.console.progress_bar');
    }

    protected function getArguments(): array
    {
        return [
            ['feed', InputArgument::OPTIONAL, 'The Feed ID for generation (from the database)'],
        ];
    }
}
