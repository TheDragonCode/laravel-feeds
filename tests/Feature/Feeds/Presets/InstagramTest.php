<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Presets\InstagramFeedPreset;
use DragonCode\LaravelFeed\Services\GeneratorService;
use Illuminate\Database\Eloquent\Builder;
use Workbench\App\Models\Product;

final class InstagramMetadataFeed extends InstagramFeedPreset
{
    public function builder(): Builder
    {
        return Product::query();
    }

    public function filename(): string
    {
        return 'instagram-metadata.xml';
    }
}

test('serializes channel metadata as XML text', function () {
    $name = 'Shop & Partners <"Main"> \'Europe\' &amp; Co';
    $url  = 'https://example.com/catalog?query=<summer>&partner="A&B"&literal=&amp;';

    config()?->set([
        'app.name' => $name,
        'app.url'  => $url,
    ]);

    $feed = app(InstagramMetadataFeed::class);

    foreach ([false, true] as $pretty) {
        setPrettyXml($pretty);

        app(GeneratorService::class)->feed($feed);

        $document = parseXmlDocument(readFeedFile($feed->path()));
        $channel  = $document->getElementsByTagName('channel')->item(0);

        expect($document->documentElement?->nodeName)
            ->toBe('rss')
            ->and($channel)
            ->toBeInstanceOf(DOMElement::class)
            ->and($channel->getElementsByTagName('title')->item(0)?->textContent)
            ->toBe($name)
            ->and($channel->getElementsByTagName('link')->item(0)?->textContent)
            ->toBe($url);
    }
});
