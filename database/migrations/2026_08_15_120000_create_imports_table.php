<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('filename');
            $table->string('original_filename');
            $table->string('type', 20)->default('csv');
            $table->string('status', 20)->default('uploaded');
            $table->unsignedBigInteger('total_rows')->default(0);
            $table->unsignedBigInteger('imported_rows')->default(0);
            $table->unsignedBigInteger('duplicate_rows')->default(0);
            $table->unsignedBigInteger('failed_rows')->default(0);
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('finished_at')->nullable();
            $table->jsonb('metadata')->default(DB::raw("'{}'::jsonb"));
            $table->timestampsTz();
            $table->index('user_id');
            $table->index('status');
            $table->index('type');
            $table->index('created_at');
            $table->index('started_at');
            $table->index('finished_at');
        });

        DB::statement("ALTER TABLE imports ADD CONSTRAINT imports_type_check CHECK (type IN ('csv'))");
        DB::statement("ALTER TABLE imports ADD CONSTRAINT imports_status_check CHECK (status IN ('uploaded', 'processing', 'parsed', 'failed'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('imports');
    }
};
