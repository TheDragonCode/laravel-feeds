<?php

declare(strict_types=1);

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Tests\database\factories\NewsFactory;

#[UseFactory(NewsFactory::class)]
class News extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
    ];
}
