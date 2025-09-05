<?php

declare(strict_types=1);

namespace Workbench\App\Feeds\Docs;

use DragonCode\LaravelFeed\Data\ElementData;
use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Feeds\Items\FeedItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Workbench\App\Feeds\Docs\Items\ReceiptInstagramFeedItem;
use Workbench\App\Models\Product;

class ReceiptInstagramFeed extends Feed
{
    public function builder(): Builder
    {
        return Product::query();
    }

    public function root(): ElementData
    {
        return new ElementData('offers');
    }

    public function header(): string
    {
        $name = config('app.name');
        $url  = config('app.url');

        return <<<XML
            <rss xmlns:g="http://base.google.com/ns/1.0" version="2.0">
            <channel>
                <title>$name</title>
                <link>$url</link>

            XML;
    }

    public function footer(): string
    {
        return "\n</channel>\n</rss>";
    }

    public function item(Model $model): FeedItem
    {
        return new ReceiptInstagramFeedItem($model);
    }

    public function filename(): string
    {
        return '../../../../../../../../../docs/snippets/receipt-instagram-feed.xml';
    }
}
