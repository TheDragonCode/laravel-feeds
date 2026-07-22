<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Presets;

use DragonCode\LaravelFeed\Data\ElementData;
use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Feeds\Items\FeedItem;
use DragonCode\LaravelFeed\Presets\Items\InstagramFeedItem;
use Illuminate\Database\Eloquent\Model;

use function config;
use function htmlspecialchars;

abstract class InstagramFeedPreset extends Feed
{
    public function root(): ElementData
    {
        return new ElementData('offers');
    }

    public function item(Model $model): FeedItem
    {
        return new InstagramFeedItem($model);
    }

    public function header(): string
    {
        $name = $this->escapeXmlText((string) config('app.name'));
        $url  = $this->escapeXmlText((string) config('app.url'));

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

    private function escapeXmlText(string $value): string
    {
        return htmlspecialchars(
            string       : $value,
            flags        : ENT_QUOTES | ENT_SUBSTITUTE | ENT_XML1,
            encoding     : 'UTF-8',
            double_encode: true
        );
    }
}
