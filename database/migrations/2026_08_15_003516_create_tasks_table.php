<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table): void {
            $table->id();

            $table->string('title');
            $table->text('description')->nullable();

            $table->string('status', 32)->default('pending');
            $table->string('priority', 16)->default('medium');

            $table->foreignId('company_id')
                ->nullable()
                ->constrained('companies')
                ->nullOnDelete();

            $table->foreignId('contact_id')
                ->nullable()
                ->constrained('contacts')
                ->nullOnDelete();

            $table->foreignId('lead_id')
                ->nullable()
                ->constrained('leads')
                ->nullOnDelete();

            $table->foreignId('opportunity_id')
                ->nullable()
                ->constrained('opportunities')
                ->nullOnDelete();

            $table->foreignId('assigned_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('created_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('due_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('priority');
            $table->index('due_at');
            $table->index('assigned_user_id');

            $table->index(['status', 'due_at']);
            $table->index(['assigned_user_id', 'status']);
            $table->index(['company_id', 'due_at']);
            $table->index(['contact_id', 'due_at']);
            $table->index(['lead_id', 'due_at']);
            $table->index(['opportunity_id', 'due_at']);
        });

        DB::statement("
            ALTER TABLE tasks
            ADD CONSTRAINT tasks_status_check
            CHECK (
                status IN (
                    'pending',
                    'in_progress',
                    'completed',
                    'cancelled'
                )
            )
        ");

        DB::statement("
            ALTER TABLE tasks
            ADD CONSTRAINT tasks_priority_check
            CHECK (
                priority IN (
                    'low',
                    'medium',
                    'high',
                    'urgent'
                )
            )
        ");

        DB::statement("
            ALTER TABLE tasks
            ADD CONSTRAINT tasks_terminal_state_check
            CHECK (
                (
                    status = 'completed'
                    AND completed_at IS NOT NULL
                    AND cancelled_at IS NULL
                )
                OR (
                    status = 'cancelled'
                    AND cancelled_at IS NOT NULL
                    AND completed_at IS NULL
                )
                OR (
                    status IN ('pending', 'in_progress')
                    AND completed_at IS NULL
                    AND cancelled_at IS NULL
                )
            )
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
