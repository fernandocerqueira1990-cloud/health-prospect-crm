<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->foreignId('first_touch_source_event_id')
                ->nullable();

            $table->foreignId('last_touch_source_event_id')
                ->nullable();

            $table->foreign('first_touch_source_event_id')
                ->references('id')
                ->on('lead_source_events')
                ->nullOnDelete();

            $table->foreign('last_touch_source_event_id')
                ->references('id')
                ->on('lead_source_events')
                ->nullOnDelete();

            $table->index('first_touch_source_event_id');
            $table->index('last_touch_source_event_id');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->dropForeign(['first_touch_source_event_id']);
            $table->dropForeign(['last_touch_source_event_id']);

            $table->dropIndex(['first_touch_source_event_id']);
            $table->dropIndex(['last_touch_source_event_id']);

            $table->dropColumn([
                'first_touch_source_event_id',
                'last_touch_source_event_id',
            ]);
        });
    }
};
