<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Commands;

use DragonCode\LaravelFeed\Models\Feed;
use DragonCode\LaravelFeed\Queries\FeedQuery;
use DragonCode\LaravelFeed\Services\AgentService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Attribute\AsCommand;

use function array_keys;
use function Laravel\Prompts\info;
use function Laravel\Prompts\table;

#[AsCommand('feed:list', 'List all feeds')]
final class FeedListCommand extends Command
{
    public function handle(FeedQuery $query, AgentService $detector): int
    {
        $items = $this->getItems($query);

        $detector->isAgent()
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
        ]));
    }

    protected function getItems(FeedQuery $query): Collection
    {
        return $query->all(true)
            ->reorder()
            ->orderByRaw('deleted_at IS NULL')
            ->orderByDesc('is_active')
            ->orderBy('title')
            ->orderBy('id')
            ->get()
            ->map(fn (Feed $feed) => $this->map($feed));
    }

    protected function map(Model $feed): array
    {
        return [
            'id' => $feed->getKey(),

            'slug'  => $feed->slug,
            'title' => $feed->title,

            'expression' => $feed->expression,

            'is_active'  => $feed->is_active ? 'true' : 'false',
            'is_deleted' => $feed->deleted_at !== null ? 'true' : 'false',

            'last_activity' => $feed->last_activity?->toIso8601String(),
        ];
    }
}
