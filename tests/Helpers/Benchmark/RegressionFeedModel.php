<?php

declare(strict_types=1);

namespace Tests\Helpers\Benchmark;

use Illuminate\Database\Eloquent\Model;

final class RegressionFeedModel extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    public function toArray(): array
    {
        return $this->getAttributes();
    }
}
