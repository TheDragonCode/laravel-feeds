<?php

declare(strict_types=1);

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Workbench\Database\Factories\ProductFactory;

#[UseFactory(ProductFactory::class)]
class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'article',
        'title',
        'description',

        'price',
        'quantity',
        'currency',

        'brand',

        'images',
    ];

    protected $casts = [
        'images' => 'array',
    ];

    public function url(): Attribute
    {
        return new Attribute(
            get: fn () => route('products.show', $this->slug)
        );
    }
}
