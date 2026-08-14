<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_source_events', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('lead_id')
                ->constrained('leads')
                ->cascadeOnDelete();

            $table->string('event_type', 64)->default('touch');

            // Valores capturados originalmente.
            $table->string('source')->nullable();
            $table->string('medium')->nullable();
            $table->string('campaign')->nullable();
            $table->string('channel')->nullable();

            // Navegação / atribuição.
            $table->text('referrer')->nullable();
            $table->text('landing_page')->nullable();

            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_content')->nullable();
            $table->string('utm_term')->nullable();

            // Redes sociais e integrações externas.
            $table->string('social_network')->nullable();
            $table->string('external_id')->nullable();

            $table->timestamp('occurred_at');

            $table->jsonb('metadata')->nullable();

            $table->timestamps();

            $table->index('event_type');
            $table->index('occurred_at');
            $table->index('utm_source');
            $table->index('utm_campaign');
            $table->index('social_network');
            $table->index(['lead_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_source_events');
    }
};
