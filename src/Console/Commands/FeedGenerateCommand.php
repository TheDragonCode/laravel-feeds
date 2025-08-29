<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Console\Commands;

use DragonCode\LaravelFeed\Services\Generator;
use Illuminate\Console\Command;
use Laravel\Prompts\Concerns\Colors;
use Symfony\Component\Console\Attribute\AsCommand;

use function app;
use function config;

#[AsCommand('feed:generate', 'Generate XML feeds')]
class FeedGenerateCommand extends Command
{
    use Colors;

    public function handle(Generator $generator): void
    {
        foreach ($this->feedable() as $feed => $enabled) {
            $enabled
                ? $this->components->task($feed, fn () => $generator->feed(app($feed)))
                : $this->components->twoColumnDetail($feed, $this->messageYellow('SKIP'));
        }
    }

    protected function feedable(): array
    {
        return config('feeds.channels');
    }

    protected function messageYellow(string $message): string
    {
        if ($this->option('no-ansi')) {
            return $message;
        }

        return $this->yellow($message);
    }
}
