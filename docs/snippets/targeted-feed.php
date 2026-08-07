<?php

declare(strict_types=1);

namespace App\Feeds;

use App\Models\Partner;
use App\Models\Product;
use DragonCode\LaravelFeed\Concerns\InteractsWithFeedTargets;
use DragonCode\LaravelFeed\Contracts\HasFeedTargets;
use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Feeds\FeedTarget;
use Illuminate\Database\Eloquent\Builder;

class PartnerFeed extends Feed implements HasFeedTargets
{
    use InteractsWithFeedTargets;

    public function targets(): iterable
    {
        foreach (Partner::query()->select('id')->lazyById() as $partner) {
            yield $this->makeTarget($partner);
        }
    }

    public function findTarget(string $key): ?FeedTarget
    {
        $partner = Partner::query()->find($key);

        if (! $partner || (string) $partner->getKey() !== $key) {
            return null;
        }

        return $this->makeTarget($partner);
    }

    public function builder(): Builder
    {
        return Product::query()->where(
            'partner_id',
            $this->target()->parameters['partner_id'],
        );
    }

    public function filename(): string
    {
        return "partners/{$this->target()->key}.xml";
    }

    private function makeTarget(Partner $partner): FeedTarget
    {
        return new FeedTarget(
            key       : (string) $partner->getKey(),
            parameters: ['partner_id' => $partner->getKey()],
        );
    }
}
