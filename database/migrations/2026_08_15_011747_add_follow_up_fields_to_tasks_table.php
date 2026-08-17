<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->boolean('is_follow_up')
                ->default(false)
                ->after('priority');

            $table->string('follow_up_channel', 32)
                ->nullable()
                ->after('is_follow_up');

            $table->foreignId('completed_activity_id')
                ->nullable()
                ->after('opportunity_id')
                ->constrained('activities')
                ->nullOnDelete();

            $table->index('is_follow_up');
            $table->index('follow_up_channel');
            $table->index('completed_activity_id');
        });

        DB::statement("
            ALTER TABLE tasks
            ADD CONSTRAINT tasks_follow_up_channel_check
            CHECK (
                follow_up_channel IS NULL
                OR follow_up_channel IN (
                    'call',
                    'email',
                    'whatsapp',
                    'meeting'
                )
            )
        ");

        DB::statement('
            ALTER TABLE tasks
            ADD CONSTRAINT tasks_follow_up_definition_check
            CHECK (
                (
                    is_follow_up = false
                    AND follow_up_channel IS NULL
                    AND completed_activity_id IS NULL
                )
                OR (
                    is_follow_up = true
                    AND follow_up_channel IS NOT NULL
                    AND due_at IS NOT NULL
                    AND (
                        company_id IS NOT NULL
                        OR contact_id IS NOT NULL
                        OR lead_id IS NOT NULL
                        OR opportunity_id IS NOT NULL
                    )
                )
            )
        ');

        DB::statement("
            ALTER TABLE tasks
            ADD CONSTRAINT tasks_completed_activity_check
            CHECK (
                completed_activity_id IS NULL
                OR (
                    is_follow_up = true
                    AND status = 'completed'
                )
            )
        ");
    }

    public function down(): void
    {
        DB::statement('
            ALTER TABLE tasks
            DROP CONSTRAINT IF EXISTS tasks_completed_activity_check
        ');

        DB::statement('
            ALTER TABLE tasks
            DROP CONSTRAINT IF EXISTS tasks_follow_up_definition_check
        ');

        DB::statement('
            ALTER TABLE tasks
            DROP CONSTRAINT IF EXISTS tasks_follow_up_channel_check
        ');

        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('completed_activity_id');
            $table->dropColumn([
                'follow_up_channel',
                'is_follow_up',
            ]);
        });
    }
};
