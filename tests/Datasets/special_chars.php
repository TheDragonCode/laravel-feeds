<?php

declare(strict_types=1);

dataset('special chars allow', [
    'simple string'    => ['Hello'],
    'string with html' => ['<b>&"\'</b>'],
    'null'             => [null],
    'int'              => [123],
    'bool true'        => [true],
    'bool false'       => [false],
    'emoji'            => ['😀'],
]);
