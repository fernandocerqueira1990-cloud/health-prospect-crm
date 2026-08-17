<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pipelines', function (Blueprint $table): void {
            $table->id();

            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();

            $table->boolean('active')->default(true);
            $table->boolean('is_default')->default(false);

            $table->timestamps();

            $table->unique('slug');
            $table->index('name');
            $table->index('active');
        });

        DB::statement('
            CREATE UNIQUE INDEX pipelines_one_default_unique
            ON pipelines (is_default)
            WHERE is_default = true
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists('pipelines');
    }
};
