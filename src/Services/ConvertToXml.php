<?php

declare(strict_types=1);

namespace DragonCode\LaravelFeed\Services;

use Spatie\ArrayToXml\ArrayToXml;

class ConvertToXml
{
    public function __construct(
        protected bool $pretty = false
    ) {}

    public function convert(array $data): string
    {
        $converter = $this->create($data)->dropXmlDeclaration();

        if ($this->pretty) {
            $converter->prettify();
        }

        return $converter->toXml();
    }

    protected function create(array $data): ArrayToXml
    {
        return new ArrayToXml($data, replaceSpacesByUnderScoresInKeyNames: false);
    }
}
