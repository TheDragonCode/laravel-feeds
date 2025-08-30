<?php

declare(strict_types=1);

namespace Workbench\App\Feeds;

use DragonCode\LaravelFeed\Data\ElementData;
use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Feeds\Items\FeedItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Workbench\App\Models\Product;

class YandexFeed extends Feed
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
        $date  = '2025-08-30T21:14:49+00:00';
        $name  = config('app.name');
        $url   = config('app.url');
        $email = config('emails.manager');

        return <<<XML
            <!DOCTYPE yml_catalog SYSTEM "shops.dtd">
            <yml_catalog date="$date">
                <shop>
                    <name>$name</name>
                    <company>$name</company>
                    <url>$url</url>
                    <platform>$name</platform>
                    <email>$email</email>
                    <currencies>
                        <currency id="RUR" rate="1"/>
                    </currencies>
                    <categories>
                        <category id="41">Домашние майки</category>
                        <category id="539">Велосипедки</category>
                        <category id="44">Ремни</category>
                    </categories>
            XML;
    }

    public function footer(): string
    {
        return "\n</shop>\n</yml_catalog>";
    }

    public function filename(): string
    {
        return 'yandex.xml';
    }

    public function item(Model $model): FeedItem
    {
        return new Items\YandexFeedItem($model);
    }
}
