<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Commands;

use Cron\CronExpression;
use DateTimeInterface;
use DragonCode\LaravelFeed\Models\Feed;
use DragonCode\LaravelFeed\Queries\FeedQuery;
use DragonCode\LaravelFeed\Services\AgentService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Output\OutputInterface;

use function array_keys;
use function config;
use function Laravel\Prompts\info;
use function Laravel\Prompts\table;
use function now;

#[AsCommand('feed:list', 'List all feeds')]
final class FeedListCommand extends Command
{
    public function handle(FeedQuery $query, AgentService $detector): int
    {
        $isAgent = $detector->isAgent();

        $items = $this->getItems($query, $isAgent);

        $isAgent
            ? $this->runForAgent($items, $detector)
            : $this->runForConsole($items);

        return self::SUCCESS;
    }

    protected function runForConsole(Collection $items): void
    {
        if ($items->isEmpty()) {
            info('No feeds available');

            return;
        }

        $headers = array_keys($items?->first() ?? []);

        table($headers, $items);
    }

    protected function runForAgent(Collection $items, AgentService $detector): void
    {
        $this->output->write($detector->encode([
            'tool'   => 'feed:list',
            'result' => 'success',
            'feeds'  => $items->toArray(),
        ]), options: OutputInterface::OUTPUT_RAW);
    }

    protected function getItems(FeedQuery $query, bool $isAgent): Collection
    {
        return $query->all(true)
            ->reorder()
            ->orderByRaw('CASE WHEN deleted_at IS NULL THEN 0 ELSE 1 END')
            ->orderByDesc('is_active')
            ->orderBy('title')
            ->orderBy('id')
            ->get()
            ->map(fn (Feed $feed) => $this->map($feed, $isAgent));
    }

    protected function map(Model $feed, bool $isAgent): array
    {
        return [
            'id' => $feed->getKey(),

            'slug'  => $feed->slug,
            'title' => $feed->title,

            'expression' => $feed->expression,
            'next_run'   => $this->nextRunDate($feed->expression),

            'is_active'  => $this->transformBool($feed->is_active, ! $isAgent),
            'is_deleted' => $this->transformBool($feed->trashed(), ! $isAgent),

            'last_activity' => $feed->last_activity?->toIso8601String(),
        ];
    }

    protected function transformBool(bool $value, bool $transform): bool|string
    {
        if (! $transform) {
            return $value;
        }

        return $value ? 'true' : 'false';
    }

    protected function nextRunDate(string $expression): ?string
    {
        try {
            return new CronExpression($expression)
                ->getNextRunDate(now(), timeZone: $this->getTimeZone())
                ->format(DateTimeInterface::ATOM);
        } catch (RuntimeException) {
            return null;
        }
    }

    protected function getTimeZone(): string
    {
        return config()->string('app.timezone');
    }
}
