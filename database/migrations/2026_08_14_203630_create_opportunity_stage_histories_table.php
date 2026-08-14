<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opportunity_stage_histories', function (Blueprint $table): void {
            $table->id();

            $table->unsignedBigInteger('pipeline_id');
            $table->unsignedBigInteger('opportunity_id');

            $table->unsignedBigInteger('from_stage_id')->nullable();
            $table->unsignedBigInteger('to_stage_id');

            $table->foreignId('changed_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('changed_at');
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->foreign(
                ['pipeline_id', 'opportunity_id'],
                'opportunity_stage_histories_opportunity_foreign',
            )
                ->references(['pipeline_id', 'id'])
                ->on('opportunities')
                ->cascadeOnDelete();

            $table->foreign(
                ['pipeline_id', 'from_stage_id'],
                'opportunity_stage_histories_from_stage_foreign',
            )
                ->references(['pipeline_id', 'id'])
                ->on('stages')
                ->restrictOnDelete();

            $table->foreign(
                ['pipeline_id', 'to_stage_id'],
                'opportunity_stage_histories_to_stage_foreign',
            )
                ->references(['pipeline_id', 'id'])
                ->on('stages')
                ->restrictOnDelete();

            $table->index(
                ['opportunity_id', 'changed_at'],
                'opportunity_stage_histories_timeline_index',
            );

            $table->index('changed_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opportunity_stage_histories');
    }
};
