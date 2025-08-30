<?php

declare(strict_types=1);

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Workbench\Database\Factories\ProductFactory;

#[UseFactory(ProductFactory::class)]
class Product extends Model
{
    use HasFactory;

    protected $fillable = [
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
}
