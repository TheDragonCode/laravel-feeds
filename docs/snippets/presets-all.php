<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Presets\InstagramFeedPreset;
use DragonCode\LaravelFeed\Presets\RssFeedPreset;
use DragonCode\LaravelFeed\Presets\SitemapFeedPreset;
use DragonCode\LaravelFeed\Presets\YandexFeedPreset;

class ProductFeed extends InstagramFeedPreset {}
class ProductFeed extends YandexFeedPreset {}
class ProductFeed extends SitemapFeedPreset {}
class ProductFeed extends RssFeedPreset {}
