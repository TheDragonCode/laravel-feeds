<?php

declare(strict_types=1);

namespace Workbench\App\Feeds\Docs;

use DragonCode\LaravelFeed\Data\ElementData;
use DragonCode\LaravelFeed\Feeds\Feed;
use DragonCode\LaravelFeed\Feeds\Info\FeedInfo;
use Illuminate\Database\Eloquent\Builder;
use Workbench\App\Feeds\Docs\Info\InfoMethodFeedInfo;
use Workbench\App\Models\User;

class InfoMethodBeforeFalseTest extends Feed
{
    public function builder(): Builder
    {
        return User::query();
    }

    public function root(): ElementData
    {
        return new ElementData(
            name      : 'info_method',
            beforeInfo: false
        );
    }

    public function info(): FeedInfo
    {
        return new InfoMethodFeedInfo;
    }

    public function filename(): string
    {
        return '../../../../../../../../../docs/snippets/advanced-element-info-before-false.xml';
    }
}
