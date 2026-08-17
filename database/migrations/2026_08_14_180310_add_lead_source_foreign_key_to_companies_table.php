<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->foreign('source_id')
                ->references('id')
                ->on('lead_sources')
                ->nullOnDelete();

            $table->index('source_id');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->dropForeign(['source_id']);
            $table->dropIndex(['source_id']);
        });
    }
};
