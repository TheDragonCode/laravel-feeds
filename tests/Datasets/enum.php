<?php

declare(strict_types=1);

use DragonCode\LaravelFeed\Enums\FeedFormatEnum;
use Workbench\App\Enums\FooEnum;

dataset('enum transform', [
    'FeedFormatEnum::Xml -> "xml"' => [FeedFormatEnum::Xml, 'xml'],
    'FooEnum::Foo -> "Foo"'        => [FooEnum::Foo, 'Foo'],
]);

dataset('enum allow', [
    'FeedFormatEnum::Xml => allowed' => [FeedFormatEnum::Xml, true],
    'FeedFormatEnum::class => deny'  => [FeedFormatEnum::class, false],
    'FooEnum::Foo => allowed'        => [FooEnum::Foo, true],
    'FooEnum::class => deny'         => [FooEnum::class, false],
    'bool true => deny'              => [true, false],
    'bool false => deny'             => [false, false],
    'string "true" => deny'          => ['true', false],
    'string "false" => deny'         => ['false', false],
    'string "0" => deny'             => ['0', false],
    'string "1" => deny'             => ['1', false],
    'int 0 => deny'                  => [0, false],
    'int 1 => deny'                  => [1, false],
    'string "foo" => deny'           => ['foo', false],
    'null => deny'                   => [null, false],
]);
