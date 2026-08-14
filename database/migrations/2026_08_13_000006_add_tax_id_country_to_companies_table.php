<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = true;

    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->string('tax_id_country', 2)->nullable()->after('tax_id');
        });

        DB::statement("ALTER TABLE companies ADD CONSTRAINT companies_tax_id_country_format_check CHECK (tax_id_country IS NULL OR tax_id_country ~ '^[A-Z]{2}$')");
        DB::statement('ALTER TABLE companies ADD CONSTRAINT companies_tax_id_country_requires_tax_id_check CHECK (tax_id IS NOT NULL OR tax_id_country IS NULL)');
        DB::statement('DROP INDEX IF EXISTS companies_tax_id_unique_not_null');
        DB::statement('CREATE UNIQUE INDEX companies_tax_id_country_unique_not_null ON companies (tax_id_country, tax_id) WHERE tax_id IS NOT NULL AND tax_id_country IS NOT NULL');
    }

    public function down(): void
    {
        $conflict = DB::table('companies')
            ->select('tax_id')
            ->whereNotNull('tax_id')
            ->groupBy('tax_id')
            ->havingRaw('COUNT(*) > ?', [1])
            ->first();

        if ($conflict !== null) {
            throw new RuntimeException(
                'Cannot rollback tax_id_country migration because multiple companies share the same tax_id across countries. Resolve the conflicting tax IDs before retrying the rollback.',
            );
        }

        DB::statement('DROP INDEX IF EXISTS companies_tax_id_country_unique_not_null');
        DB::statement('ALTER TABLE companies DROP CONSTRAINT IF EXISTS companies_tax_id_country_requires_tax_id_check');
        DB::statement('ALTER TABLE companies DROP CONSTRAINT IF EXISTS companies_tax_id_country_format_check');

        Schema::table('companies', function (Blueprint $table): void {
            $table->dropColumn('tax_id_country');
        });

        DB::statement('CREATE UNIQUE INDEX companies_tax_id_unique_not_null ON companies (tax_id) WHERE tax_id IS NOT NULL');
    }
};
