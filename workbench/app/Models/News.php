<?php

declare(strict_types=1);

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Workbench\Database\Factories\NewsFactory;

#[UseFactory(NewsFactory::class)]
class News extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
    ];
}
