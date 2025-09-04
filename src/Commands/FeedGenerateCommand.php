<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Commands;

use DragonCode\LaravelFeed\Exceptions\InvalidFeedArgumentException;
use DragonCode\LaravelFeed\Queries\FeedQuery;
use DragonCode\LaravelFeed\Services\Generator;
use Illuminate\Console\Command;
use Laravel\Prompts\Concerns\Colors;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;

use function app;
use function is_numeric;

#[AsCommand('feed:generate', 'Generate XML feeds')]
class FeedGenerateCommand extends Command
{
    use Colors;

    public function handle(Generator $generator, FeedQuery $query): void
    {
        foreach ($this->feedable($query) as $feed => $enabled) {
            $enabled
                ? $this->components->task($feed, fn () => $generator->feed(app($feed)))
                : $this->components->twoColumnDetail($feed, $this->messageYellow('SKIP'));
        }
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
            return $message;
        }

        return $this->yellow($message);
    }

    protected function getArguments(): array
    {
        return [
            ['feed', InputArgument::OPTIONAL, 'The Feed ID for generation (from the database)'],
        ];
    }
}
