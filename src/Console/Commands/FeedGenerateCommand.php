<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Console\Commands;

use DragonCode\LaravelFeed\Helpers\FeedHelper;
use DragonCode\LaravelFeed\Services\Generator;
use Illuminate\Console\Command;
use Laravel\Prompts\Concerns\Colors;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;

use function app;
use function config;

#[AsCommand('feed:generate', 'Generate XML feeds')]
class FeedGenerateCommand extends Command
{
    use Colors;

    public function handle(Generator $generator, FeedHelper $helper): void
    {
        foreach ($this->feedable($helper) as $feed => $enabled) {
            $enabled
                ? $this->components->task($feed, fn () => $generator->feed(app($feed)))
                : $this->components->twoColumnDetail($feed, $this->messageYellow('SKIP'));
        }
    }

    protected function feedable(FeedHelper $helper): array
    {
        if ($feed = $this->resolveFeedClass($helper)) {
            return [$feed => true];
        }

        return config('feeds.channels');
    }

    protected function resolveFeedClass(FeedHelper $helper): ?string
    {
        if (! $class = $this->argument('class')) {
            return null;
        }

        return $helper->find((string) $class);
    }

    protected function messageYellow(string $message): string
    {
        if ($this->option('no-ansi')) {
            return $message;
        }

        return $this->yellow($message);
    }

    protected function getArguments(): array
    {
        return [
            ['class', InputArgument::OPTIONAL, 'The feed class for generation'],
        ];
    }
}
