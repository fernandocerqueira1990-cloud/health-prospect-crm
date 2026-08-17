<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stages', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('pipeline_id')
                ->constrained('pipelines')
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('slug');

            $table->unsignedSmallInteger('position');
            $table->unsignedSmallInteger('probability')->default(0);

            $table->string('type', 16)->default('open');
            $table->boolean('active')->default(true);

            $table->timestamps();

            $table->unique(
                ['pipeline_id', 'slug'],
                'stages_pipeline_slug_unique',
            );

            $table->unique(
                ['pipeline_id', 'position'],
                'stages_pipeline_position_unique',
            );

            $table->unique(
                ['pipeline_id', 'id'],
                'stages_pipeline_id_id_unique',
            );

            $table->index(['pipeline_id', 'active']);
            $table->index(['pipeline_id', 'type']);
        });

        DB::statement("
            ALTER TABLE stages
            ADD CONSTRAINT stages_type_check
            CHECK (type IN ('open', 'won', 'lost'))
        ");

        DB::statement('
            ALTER TABLE stages
            ADD CONSTRAINT stages_probability_check
            CHECK (probability BETWEEN 0 AND 100)
        ');

        DB::statement('
            ALTER TABLE stages
            ADD CONSTRAINT stages_position_check
            CHECK (position > 0)
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists('stages');
    }
};
