<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        $this->schema()->table($this->table(), function (Blueprint $table) {
            $table->text('slug')->nullable();
        });

        $this->schema()->getConnection()->table($this->table())
            ->select(['id', 'title'])
            ->chunkById(1000, function (Collection $feeds) {
                foreach ($feeds as $feed) {
                    $this->schema()->getConnection()->table($this->table())
                        ->where('id', $feed->id)
                        ->update(['slug' => Str::slug($feed->title)]);
                }
            });
    }

    public function down(): void
    {
        $this->schema()->table($this->table(), function (Blueprint $table) {
            $table->dropColumn('slug');
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
