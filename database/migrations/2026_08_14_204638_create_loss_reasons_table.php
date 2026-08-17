<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loss_reasons', function (Blueprint $table): void {
            $table->id();

            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();

            $table->unsignedSmallInteger('position');
            $table->boolean('active')->default(true);

            $table->timestamps();

            $table->unique('slug');
            $table->unique('position');

            $table->index('name');
            $table->index('active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loss_reasons');
    }
};
