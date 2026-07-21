<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Exceptions;

use Illuminate\Filesystem\FilesystemAdapter;
use RuntimeException;

use function sprintf;

class UnsupportedStorageDiskException extends RuntimeException
{
    public function __construct(string $disk, string $storage)
    {
        parent::__construct(sprintf(
            'Feed storage disk [%s] must resolve to [%s], [%s] returned.',
            $disk,
            FilesystemAdapter::class,
            $storage
        ));
    }
}
