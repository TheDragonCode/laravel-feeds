<?php

declare(strict_types=1);

use Workbench\App\Feeds\EmptyFeed;

test('export', function (bool $pretty) {
    setPrettyXml($pretty);

    expectFeed(EmptyFeed::class);
})->with('boolean');
