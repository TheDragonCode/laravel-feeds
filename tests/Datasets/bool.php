<?php

declare(strict_types=1);

dataset('boolean', [true, false]);

dataset('bool transform', [
    'true to "true"'   => [true, 'true'],
    'false to "false"' => [false, 'false'],
]);

dataset('bool allow', [
    'bool true => allowed'   => [true, true],
    'bool false => allowed'  => [false, true],
    'string "true" => deny'  => ['true', false],
    'string "false" => deny' => ['false', false],
    'string "0" => deny'     => ['0', false],
    'string "1" => deny'     => ['1', false],
    'int 0 => deny'          => [0, false],
    'int 1 => deny'          => [1, false],
    'string "foo" => deny'   => ['foo', false],
    'null => deny'           => [null, false],
]);
