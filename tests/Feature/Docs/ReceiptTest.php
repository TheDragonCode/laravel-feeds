<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Commands\FeedGenerateCommand;
use DragonCode\LaravelFeed\Models\Feed;
use Workbench\App\Data\NewsFakeData;

use function Pest\Laravel\artisan;

it('generate stub', function (string $feed, array $files, array $replaces = []): void {
    createProducts(2);
    createNews(...NewsFakeData::toArray());

    $model = Feed::create([
        'class' => $feed,
        'title' => $feed,
    ]);

    artisan(FeedGenerateCommand::class, ['feed' => $model->id])
        ->assertSuccessful()
        ->run();

    foreach ($files as $from => $to) {
        copyFeedFileToDoc($from, $to, $replaces, false);
    }
})->with('docs receipts');
