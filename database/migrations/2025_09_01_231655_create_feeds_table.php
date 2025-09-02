<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $this->schema()->create($this->table(), function (Blueprint $table) {
            $table->id();

            $table->string('class')->unique();
            $table->string('title');

            $table->string('expression');
            $table->string('format');

            $table->boolean('is_active');

            $table->timestamp('last_activity')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        $this->schema()->dropIfExists($this->table());
    }

    protected function schema(): Builder
    {
        return Schema::connection($this->connection());
    }

    protected function connection(): ?string
    {
        return config('feeds.table.connection');
    }

    protected function table(): string
    {
        return config('feeds.table.table');
    }
};
