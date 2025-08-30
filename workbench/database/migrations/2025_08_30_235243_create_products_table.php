<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', static function (Blueprint $table) {
            $table->id();

            $table->string('article');

            $table->string('title');
            $table->text('description');
            $table->string('brand');

            $table->integer('price');
            $table->integer('quantity');
            $table->string('currency');

            $table->json('images');

            $table->timestamps();
        });
    }
};
