<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opportunities', function (Blueprint $table): void {
            $table->id();

            $table->string('title');

            $table->foreignId('lead_id')
                ->nullable()
                ->constrained('leads')
                ->nullOnDelete();

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

            $table->foreignId('pipeline_id')
                ->constrained('pipelines')
                ->restrictOnDelete();

            $table->unsignedBigInteger('stage_id');

            $table->decimal('amount', 15, 2)->default(0);
            $table->char('currency', 3)->default('BRL');

            $table->unsignedSmallInteger('probability')->default(0);

            $table->date('expected_close_date')->nullable();

            $table->timestamp('won_at')->nullable();
            $table->timestamp('lost_at')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign(['pipeline_id', 'stage_id'])
                ->references(['pipeline_id', 'id'])
                ->on('stages')
                ->restrictOnDelete();

            $table->unique(
                ['pipeline_id', 'id'],
                'opportunities_pipeline_id_id_unique',
            );

            $table->index('title');
            $table->index('lead_id');
            $table->index('company_id');
            $table->index('contact_id');
            $table->index('assigned_user_id');
            $table->index(['pipeline_id', 'stage_id']);
            $table->index('expected_close_date');
            $table->index(['pipeline_id', 'deleted_at']);
        });

        DB::statement('
            ALTER TABLE opportunities
            ADD CONSTRAINT opportunities_probability_check
            CHECK (probability BETWEEN 0 AND 100)
        ');

        DB::statement('
            ALTER TABLE opportunities
            ADD CONSTRAINT opportunities_amount_check
            CHECK (amount >= 0)
        ');

        DB::statement("
            ALTER TABLE opportunities
            ADD CONSTRAINT opportunities_currency_check
            CHECK (currency ~ '^[A-Z]{3}$')
        ");

        DB::statement('
            ALTER TABLE opportunities
            ADD CONSTRAINT opportunities_subject_check
            CHECK (lead_id IS NOT NULL OR company_id IS NOT NULL)
        ');

        DB::statement('
            ALTER TABLE opportunities
            ADD CONSTRAINT opportunities_terminal_dates_check
            CHECK (NOT (won_at IS NOT NULL AND lost_at IS NOT NULL))
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists('opportunities');
    }
};
