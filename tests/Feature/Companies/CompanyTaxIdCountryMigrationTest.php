<?php

namespace Tests\Feature\Companies;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class CompanyTaxIdCountryMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_forward_schema_allows_same_tax_id_in_different_countries(): void
    {
        $this->insertCompany('Empresa Brasil', 'SHARED-123', 'BR');
        $this->insertCompany('Empresa Chile', 'SHARED-123', 'CL');

        $this->assertSame(2, DB::table('companies')->where('tax_id', 'SHARED-123')->count());
        $this->assertTrue($this->indexExists('companies_tax_id_country_unique_not_null'));
    }

    public function test_rollback_succeeds_without_conflicts_and_preserves_tax_ids(): void
    {
        $firstId = $this->insertCompany('Empresa Um', 'UNIQUE-1', 'BR');
        $secondId = $this->insertCompany('Empresa Dois', 'UNIQUE-2', 'CL');

        $this->migration()->down();

        $this->assertFalse(Schema::hasColumn('companies', 'tax_id_country'));
        $this->assertTrue($this->indexExists('companies_tax_id_unique_not_null'));
        $this->assertSame('UNIQUE-1', DB::table('companies')->where('id', $firstId)->value('tax_id'));
        $this->assertSame('UNIQUE-2', DB::table('companies')->where('id', $secondId)->value('tax_id'));
    }

    public function test_conflicting_cross_country_tax_ids_abort_before_schema_or_data_changes(): void
    {
        $firstId = $this->insertCompany('Empresa Brasil', 'CONFLICT-1', 'BR');
        $secondId = $this->insertCompany('Empresa Chile', 'CONFLICT-1', 'CL');

        try {
            $this->migration()->down();
            $this->fail('O rollback deveria ter sido bloqueado pela colisão de tax IDs.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('multiple companies share the same tax_id across countries', $exception->getMessage());
            $this->assertStringContainsString('Resolve the conflicting tax IDs', $exception->getMessage());
        }

        $this->assertTrue(Schema::hasColumn('companies', 'tax_id_country'));
        $this->assertTrue($this->indexExists('companies_tax_id_country_unique_not_null'));
        $this->assertDatabaseHas('companies', ['id' => $firstId, 'tax_id' => 'CONFLICT-1', 'tax_id_country' => 'BR']);
        $this->assertDatabaseHas('companies', ['id' => $secondId, 'tax_id' => 'CONFLICT-1', 'tax_id_country' => 'CL']);
        $this->assertSame(2, DB::table('companies')->count());
    }

    public function test_records_without_tax_id_do_not_prevent_rollback(): void
    {
        $this->insertCompany('Sem Documento Um', null, null);
        $this->insertCompany('Sem Documento Dois', null, null);

        $this->migration()->down();

        $this->assertFalse(Schema::hasColumn('companies', 'tax_id_country'));
        $this->assertSame(2, DB::table('companies')->count());
    }

    public function test_soft_deleted_conflict_blocks_rollback_like_the_old_index_would(): void
    {
        $this->insertCompany('Empresa Ativa', 'SOFT-DELETE-CONFLICT', 'BR');
        $softDeletedId = $this->insertCompany('Empresa Excluída', 'SOFT-DELETE-CONFLICT', 'CL', now());

        $this->expectException(RuntimeException::class);

        try {
            $this->migration()->down();
        } finally {
            $this->assertTrue(Schema::hasColumn('companies', 'tax_id_country'));
            $this->assertDatabaseHas('companies', ['id' => $softDeletedId, 'tax_id' => 'SOFT-DELETE-CONFLICT']);
        }
    }

    public function test_rollback_succeeds_after_conflict_is_resolved_manually(): void
    {
        $firstId = $this->insertCompany('Empresa Brasil', 'RESOLVED-1', 'BR');
        $secondId = $this->insertCompany('Empresa Chile', 'RESOLVED-1', 'CL');

        try {
            $this->migration()->down();
            $this->fail('A primeira tentativa deveria falhar.');
        } catch (RuntimeException) {
            DB::table('companies')->where('id', $secondId)->update(['tax_id' => 'RESOLVED-2']);
        }

        $this->migration()->down();

        $this->assertFalse(Schema::hasColumn('companies', 'tax_id_country'));
        $this->assertSame('RESOLVED-1', DB::table('companies')->where('id', $firstId)->value('tax_id'));
        $this->assertSame('RESOLVED-2', DB::table('companies')->where('id', $secondId)->value('tax_id'));
    }

    public function test_forward_can_be_reexecuted_after_valid_rollback(): void
    {
        $companyId = $this->insertCompany('Empresa Reversível', 'ROUNDTRIP-1', 'BR');
        $migration = $this->migration();

        $migration->down();
        $migration->up();

        $this->assertTrue(Schema::hasColumn('companies', 'tax_id_country'));
        $this->assertTrue($this->indexExists('companies_tax_id_country_unique_not_null'));
        $this->assertSame('ROUNDTRIP-1', DB::table('companies')->where('id', $companyId)->value('tax_id'));
        $this->assertNull(DB::table('companies')->where('id', $companyId)->value('tax_id_country'));
    }

    private function migration(): Migration
    {
        return require database_path('migrations/2026_08_13_000006_add_tax_id_country_to_companies_table.php');
    }

    private function indexExists(string $index): bool
    {
        return DB::table('pg_indexes')
            ->where('schemaname', DB::raw('current_schema()'))
            ->where('tablename', 'companies')
            ->where('indexname', $index)
            ->exists();
    }

    private function insertCompany(string $legalName, ?string $taxId, ?string $country, mixed $deletedAt = null): int
    {
        return (int) DB::table('companies')->insertGetId([
            'legal_name' => $legalName,
            'tax_id' => $taxId,
            'tax_id_country' => $country,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => $deletedAt,
        ]);
    }
}
