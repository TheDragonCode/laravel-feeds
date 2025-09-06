<?php

declare(strict_types=1);

dataset('queries.create.class.invalid', [
    'foo',
    '123',
    'foo 1 2 3',
    '* * * * * *',
    '* * *',
]);
