<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('opportunities', function (Blueprint $table): void {
            $table->foreignId('loss_reason_id')
                ->nullable()
                ->after('lost_at')
                ->constrained('loss_reasons')
                ->restrictOnDelete();

            $table->index('loss_reason_id');
        });

        DB::statement('
            ALTER TABLE opportunities
            ADD CONSTRAINT opportunities_loss_reason_state_check
            CHECK (
                (lost_at IS NULL AND loss_reason_id IS NULL)
                OR
                (lost_at IS NOT NULL AND loss_reason_id IS NOT NULL)
            )
        ');
    }

    public function down(): void
    {
        DB::statement('
            ALTER TABLE opportunities
            DROP CONSTRAINT IF EXISTS opportunities_loss_reason_state_check
        ');

        Schema::table('opportunities', function (Blueprint $table): void {
            $table->dropForeign(['loss_reason_id']);
            $table->dropIndex(['loss_reason_id']);
            $table->dropColumn('loss_reason_id');
        });
    }
};
