<?php

declare(strict_types=1);

namespace App\Feeds;

use App\Models\User;
use DragonCode\LaravelFeed\Feeds\Feed;
use Illuminate\Database\Eloquent\Builder;

class HeaderFooterFeed extends Feed
{
    public function builder(): Builder
    {
        return User::query();
    }

    public function header(): string
    {
        return '<rss xmlns:g="http://base.google.com/ns/1.0" version="2.0">';
    }

    public function footer(): string
    {
        return "\n<g:footer>This is a custom footer element</g:footer>";
    }
}
