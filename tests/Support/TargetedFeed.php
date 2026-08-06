<?php

declare(strict_types=1);

namespace Tests\Support;

use DragonCode\LaravelFeed\Contracts\HasFeedTargets;
use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Feeds\FeedTarget;
use Illuminate\Database\Eloquent\Builder;
use Workbench\App\Models\User;

use function in_array;
use function sprintf;

class TargetedFeed extends Feed implements HasFeedTargets
{
    public static array $keys = ['42', '81'];

    public static array $yieldedKeys = [];

    public static array $findTargetCalls = [];

    public static int $targetsCalls = 0;

    public static function resetState(): void
    {
        self::$keys            = ['42', '81'];
        self::$yieldedKeys     = [];
        self::$findTargetCalls = [];
        self::$targetsCalls    = 0;
    }

    public function targets(): iterable
    {
        self::$targetsCalls++;

        foreach (self::$keys as $key) {
            self::$yieldedKeys[] = $key;

            yield $this->makeTarget($key);
        }
    }

    public function findTarget(string $key): ?FeedTarget
    {
        self::$findTargetCalls[] = $key;

        if (! in_array($key, self::$keys, true)) {
            return null;
        }

        return $this->makeTarget($key);
    }

    public function builder(): Builder
    {
        return User::query();
    }

    public function filename(): string
    {
        return $this->filename ??= sprintf('partners/%s.xml', $this->target()->key);
    }

    protected function makeTarget(string $key): FeedTarget
    {
        return new FeedTarget(
            key: $key,
            parameters: ['partner_id' => (int) $key],
        );
    }
}
