<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->string('name');
            $table->string('job_title')->nullable();
            $table->string('department')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 64)->nullable();
            $table->string('whatsapp', 64)->nullable();
            $table->string('linkedin_url', 2048)->nullable();
            $table->string('decision_role', 32)->nullable();
            $table->string('influence_level', 16)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('name');
            $table->index('decision_role');
            $table->index('influence_level');
            $table->index('active');
        });

        DB::statement('CREATE INDEX contacts_email_index ON contacts (email) WHERE email IS NOT NULL');
        DB::statement('CREATE UNIQUE INDEX contacts_one_active_primary_per_company ON contacts (company_id) WHERE is_primary = true AND active = true AND deleted_at IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
