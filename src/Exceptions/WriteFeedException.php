<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Exceptions;

use RuntimeException;

// @codeCoverageIgnoreStart
class WriteFeedException extends RuntimeException
{
    public function __construct(
        string $path,
        private readonly ?int $writtenBytes = null,
        private readonly ?int $expectedBytes = null,
    ) {
        if ($writtenBytes === null || $expectedBytes === null) {
            parent::__construct("Failed to write to the feed: [$path].");

            return;
        }

        parent::__construct(
            "Failed to write to the feed: [$path]. Written [$writtenBytes] of [$expectedBytes] bytes."
        );
    }

    public function withPath(string $path): static
    {
        $this->message = new self($path, $this->writtenBytes, $this->expectedBytes)->getMessage();

        return $this;
    }
}
// @codeCoverageIgnoreEnd
