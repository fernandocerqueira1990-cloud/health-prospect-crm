<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = true;

    public function up(): void
    {
        Schema::table('import_rows', function (Blueprint $table): void {
            $table->jsonb('execution_data')->nullable()->after('dedup_data');
        });

        DB::statement('ALTER TABLE imports DROP CONSTRAINT imports_status_check');
        DB::statement("ALTER TABLE imports ADD CONSTRAINT imports_status_check CHECK (status IN ('uploaded', 'processing', 'parsed', 'completed', 'failed'))");
    }

    public function down(): void
    {
        if (DB::table('imports')->where('status', 'completed')->exists()) {
            throw new RuntimeException('Cannot rollback final import execution while completed imports exist.');
        }

        DB::statement('ALTER TABLE imports DROP CONSTRAINT imports_status_check');
        DB::statement("ALTER TABLE imports ADD CONSTRAINT imports_status_check CHECK (status IN ('uploaded', 'processing', 'parsed', 'failed'))");

        Schema::table('import_rows', function (Blueprint $table): void {
            $table->dropColumn('execution_data');
        });
    }
};
