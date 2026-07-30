<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $this->schema()->table($this->table(), function (Blueprint $table) {
            $table->boolean('is_available_fulfilment')->default(false);
            $table->boolean('is_available_omni')->default(false);
            $table->boolean('is_available_sfs')->default(false);
        });
    }

    public function down(): void
    {
        $this->schema()->table($this->table(), function (Blueprint $table) {
            $table->dropColumn([
                'is_available_fulfilment',
                'is_available_omni',
                'is_available_sfs',
            ]);
        });
    }

    protected function schema(): Builder
    {
        return Schema::connection(config('feeds.table.connection'));
    }

    protected function table(): string
    {
        return config('feeds.table.table');
    }
};
