<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_rows', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('import_id')->constrained('imports')->cascadeOnDelete();
            $table->unsignedBigInteger('row_number');
            $table->string('status', 20)->default('parsed');
            $table->jsonb('original_data');
            $table->jsonb('normalized_data')->nullable();
            $table->text('error_message')->nullable();
            $table->string('related_entity_type')->nullable();
            $table->unsignedBigInteger('related_entity_id')->nullable();
            $table->timestampsTz();
            $table->unique(['import_id', 'row_number']);
            $table->index('import_id');
            $table->index('status');
            $table->index(['related_entity_type', 'related_entity_id']);
            $table->index('created_at');
        });

        DB::statement("ALTER TABLE import_rows ADD CONSTRAINT import_rows_status_check CHECK (status IN ('parsed', 'failed'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('import_rows');
    }
};
