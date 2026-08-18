<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lead_source_events', function (Blueprint $table): void {
            $table->foreignId('campaign_id')
                ->nullable()
                ->after('lead_id')
                ->constrained('campaigns')
                ->nullOnDelete();

            $table->index(['campaign_id', 'lead_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::table('lead_source_events', function (Blueprint $table): void {
            $table->dropIndex(['campaign_id', 'lead_id', 'occurred_at']);
            $table->dropConstrainedForeignId('campaign_id');
        });
    }
};
