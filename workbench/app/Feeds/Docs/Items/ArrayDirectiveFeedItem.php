<?php

declare(strict_types=1);

namespace Workbench\App\Feeds\Docs\Items;

use DragonCode\LaravelFeed\Feeds\Items\FeedItem;

use function fake;

class ArrayDirectiveFeedItem extends FeedItem
{
    public function toArray(): array
    {
        return [
            'name' => $this->model->name,

            '@avatar' => [
                fake()->imageUrl(),
                fake()->imageUrl(),
            ],

            '@images' => [
                [
                    '@attributes' => ['name' => fake()->words(asText: true)],
                    '@value'      => fake()->imageUrl(),
                ],
                [
                    '@attributes' => ['name' => fake()->words(asText: true)],
                    '@value'      => fake()->imageUrl(),
                ],
            ],
        ];
    }
}
