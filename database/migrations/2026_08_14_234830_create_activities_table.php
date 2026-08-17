<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table): void {
            $table->id();

            $table->string('type', 32);
            $table->string('direction', 16)->nullable();

            $table->string('subject');
            $table->text('description')->nullable();
            $table->string('outcome')->nullable();

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

            $table->timestamp('occurred_at');
            $table->unsignedInteger('duration_minutes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('type');
            $table->index('direction');
            $table->index('occurred_at');
            $table->index('company_id');
            $table->index('contact_id');
            $table->index('lead_id');
            $table->index('opportunity_id');
            $table->index('assigned_user_id');
            $table->index('created_by_user_id');

            $table->index(['company_id', 'occurred_at']);
            $table->index(['contact_id', 'occurred_at']);
            $table->index(['lead_id', 'occurred_at']);
            $table->index(['opportunity_id', 'occurred_at']);
        });

        DB::statement("
            ALTER TABLE activities
            ADD CONSTRAINT activities_type_check
            CHECK (
                type IN (
                    'call',
                    'email',
                    'whatsapp',
                    'meeting',
                    'note',
                    'other'
                )
            )
        ");

        DB::statement("
            ALTER TABLE activities
            ADD CONSTRAINT activities_direction_check
            CHECK (
                direction IS NULL
                OR direction IN ('inbound', 'outbound')
            )
        ");

        DB::statement('
            ALTER TABLE activities
            ADD CONSTRAINT activities_subject_entity_check
            CHECK (
                company_id IS NOT NULL
                OR contact_id IS NOT NULL
                OR lead_id IS NOT NULL
                OR opportunity_id IS NOT NULL
            )
        ');

        DB::statement('
            ALTER TABLE activities
            ADD CONSTRAINT activities_duration_check
            CHECK (
                duration_minutes IS NULL
                OR duration_minutes > 0
            )
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
