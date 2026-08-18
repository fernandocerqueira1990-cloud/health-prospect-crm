<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status', 16)->default('draft');

            $table->foreignId('channel_id')
                ->nullable()
                ->constrained('channels')
                ->nullOnDelete();

            $table->foreignId('owner_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('budget', 15, 2)->nullable();
            $table->char('currency', 3)->default('BRL');

            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_content')->nullable();
            $table->string('utm_term')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('name');
            $table->index('status');
            $table->index('start_date');
            $table->index('end_date');
            $table->index(['status', 'start_date']);
        });

        DB::statement("
            ALTER TABLE campaigns
            ADD CONSTRAINT campaigns_status_check
            CHECK (
                status IN (
                    'draft',
                    'planned',
                    'active',
                    'paused',
                    'completed',
                    'cancelled'
                )
            )
        ");

        DB::statement("
            ALTER TABLE campaigns
            ADD CONSTRAINT campaigns_name_check
            CHECK (btrim(name) <> '')
        ");

        DB::statement('
            ALTER TABLE campaigns
            ADD CONSTRAINT campaigns_dates_check
            CHECK (
                start_date IS NULL
                OR end_date IS NULL
                OR end_date >= start_date
            )
        ');

        DB::statement('
            ALTER TABLE campaigns
            ADD CONSTRAINT campaigns_budget_check
            CHECK (budget IS NULL OR budget >= 0)
        ');

        DB::statement("
            ALTER TABLE campaigns
            ADD CONSTRAINT campaigns_currency_check
            CHECK (currency ~ '^[A-Z]{3}$')
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
