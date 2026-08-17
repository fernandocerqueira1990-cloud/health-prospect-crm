<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table): void {
            $table->id();

            // Dados capturados antes da conversão/vínculo.
            $table->string('name')->nullable();
            $table->string('company_name')->nullable();
            $table->string('job_title')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 64)->nullable();
            $table->string('whatsapp', 64)->nullable();

            // Entidades já existentes no CRM.
            $table->foreignId('company_id')
                ->nullable()
                ->constrained('companies')
                ->nullOnDelete();

            $table->foreignId('contact_id')
                ->nullable()
                ->constrained('contacts')
                ->nullOnDelete();

            $table->foreignId('assigned_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Atribuição comercial.
            $table->foreignId('source_id')
                ->constrained('lead_sources')
                ->restrictOnDelete();

            $table->foreignId('channel_id')
                ->constrained('channels')
                ->restrictOnDelete();

            // Qualificação.
            $table->string('status', 32)->default('new');
            $table->string('priority', 16)->nullable();
            $table->string('temperature', 16)->nullable();
            $table->unsignedSmallInteger('score')->default(0);

            // Datas comerciais.
            $table->timestamp('qualified_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamp('lost_at')->nullable();
            $table->timestamp('last_interaction_at')->nullable();
            $table->timestamp('next_action_at')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('name');
            $table->index('company_name');
            $table->index('email');
            $table->index('status');
            $table->index('priority');
            $table->index('temperature');
            $table->index('score');
            $table->index('last_interaction_at');
            $table->index('next_action_at');
            $table->index(['status', 'assigned_user_id']);
            $table->index(['source_id', 'channel_id']);
        });

        DB::statement("
            ALTER TABLE leads
            ADD CONSTRAINT leads_status_check
            CHECK (
                status IN (
                    'new',
                    'contacted',
                    'qualified',
                    'nurturing',
                    'converted',
                    'disqualified'
                )
            )
        ");

        DB::statement("
            ALTER TABLE leads
            ADD CONSTRAINT leads_priority_check
            CHECK (
                priority IS NULL
                OR priority IN ('low', 'medium', 'high', 'critical')
            )
        ");

        DB::statement("
            ALTER TABLE leads
            ADD CONSTRAINT leads_temperature_check
            CHECK (
                temperature IS NULL
                OR temperature IN ('cold', 'warm', 'hot')
            )
        ");

        DB::statement('
            ALTER TABLE leads
            ADD CONSTRAINT leads_score_check
            CHECK (score BETWEEN 0 AND 100)
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
