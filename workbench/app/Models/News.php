<?php

declare(strict_types=1);

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Workbench\Database\Factories\NewsFactory;

use function route;

#[UseFactory(NewsFactory::class)]
class News extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',

        'category',
    ];

    public function url(): Attribute
    {
        return new Attribute(
            get: fn () => route('news.show', Str::slug($this->title))
        );
    }
}
