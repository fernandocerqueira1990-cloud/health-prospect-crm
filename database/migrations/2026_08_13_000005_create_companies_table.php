<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table): void {
            $table->id();
            $table->string('legal_name');
            $table->string('trade_name')->nullable();
            $table->string('tax_id', 64)->nullable();
            $table->string('segment')->nullable();
            $table->string('category')->nullable();
            $table->string('website', 2048)->nullable();
            $table->string('phone', 64)->nullable();
            $table->string('email')->nullable();
            $table->string('street')->nullable();
            $table->string('number', 32)->nullable();
            $table->string('complement')->nullable();
            $table->string('district')->nullable();
            $table->string('city')->nullable();
            $table->string('state', 100)->nullable();
            $table->string('postal_code', 32)->nullable();
            $table->unsignedInteger('employee_count_estimate')->nullable();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('source_id')->nullable()->comment('Reserved for the future lead_sources module.');
            $table->string('priority', 16)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('legal_name');
            $table->index('city');
            $table->index('state');
            $table->index('assigned_user_id');
            $table->index('priority');
        });

        DB::statement('CREATE UNIQUE INDEX companies_tax_id_unique_not_null ON companies (tax_id) WHERE tax_id IS NOT NULL');
        DB::statement('CREATE INDEX companies_trade_name_index ON companies (trade_name) WHERE trade_name IS NOT NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
