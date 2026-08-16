<?php

namespace Tests\Feature\Imports;

use App\Models\DataImport;
use App\Models\ImportRow;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ImportStructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_tables_have_expected_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('imports', ['id', 'user_id', 'filename', 'original_filename', 'type', 'status', 'total_rows', 'imported_rows', 'duplicate_rows', 'failed_rows', 'started_at', 'finished_at', 'metadata', 'created_at', 'updated_at']));
        $this->assertTrue(Schema::hasColumns('import_rows', ['id', 'import_id', 'row_number', 'status', 'original_data', 'normalized_data', 'dedup_data', 'execution_data', 'error_message', 'related_entity_type', 'related_entity_id', 'created_at', 'updated_at']));
    }

    public function test_import_relationships_resolve(): void
    {
        $user = User::factory()->create();
        $dataImport = DataImport::factory()->for($user)->create();
        $row = ImportRow::factory()->for($dataImport, 'dataImport')->create();

        $this->assertTrue($dataImport->user->is($user));
        $this->assertTrue($dataImport->rows->first()->is($row));
        $this->assertTrue($row->dataImport->is($dataImport));
        $this->assertTrue($user->dataImports->first()->is($dataImport));
        $this->assertSame(['Nome' => $row->original_data['Nome']], $row->original_data);
        $this->assertNull($row->normalized_data);
    }

    public function test_import_defaults_are_applied_by_postgresql(): void
    {
        $dataImport = DataImport::query()->create([
            'user_id' => User::factory()->create()->id,
            'filename' => 'internal.csv',
            'original_filename' => 'original.csv',
        ])->refresh();

        $this->assertSame(DataImport::TYPE_CSV, $dataImport->type);
        $this->assertSame(DataImport::STATUS_UPLOADED, $dataImport->status);
        $this->assertSame(0, $dataImport->total_rows);
        $this->assertSame(0, $dataImport->imported_rows);
        $this->assertSame(0, $dataImport->duplicate_rows);
        $this->assertSame(0, $dataImport->failed_rows);
        $this->assertSame([], $dataImport->metadata);
    }

    public function test_import_user_foreign_key_restricts_user_deletion(): void
    {
        $user = User::factory()->create();
        DataImport::factory()->for($user)->create();

        $this->expectException(QueryException::class);
        $user->delete();
    }

    public function test_deleting_import_cascades_rows(): void
    {
        $dataImport = DataImport::factory()->create();
        ImportRow::factory()->for($dataImport, 'dataImport')->create();

        $dataImport->delete();

        $this->assertDatabaseCount('import_rows', 0);
    }

    public function test_database_rejects_invalid_import_type(): void
    {
        $this->expectException(QueryException::class);
        DataImport::factory()->create(['type' => 'xls']);
    }

    public function test_database_accepts_xlsx_import_type(): void
    {
        $dataImport = DataImport::factory()->create(['type' => DataImport::TYPE_XLSX]);

        $this->assertSame(DataImport::TYPE_XLSX, $dataImport->type);
    }

    public function test_database_rejects_invalid_import_status(): void
    {
        $this->expectException(QueryException::class);
        DataImport::factory()->create(['status' => 'unknown']);
    }

    public function test_database_accepts_completed_import_status(): void
    {
        $dataImport = DataImport::factory()->create(['status' => DataImport::STATUS_COMPLETED]);

        $this->assertSame(DataImport::STATUS_COMPLETED, $dataImport->status);
    }

    public function test_database_rejects_invalid_import_row_status(): void
    {
        $this->expectException(QueryException::class);
        ImportRow::factory()->create(['status' => 'unknown']);
    }

    public function test_import_indexes_and_foreign_keys_exist_with_expected_actions(): void
    {
        $importIndexes = DB::table('pg_indexes')->where('schemaname', 'public')->where('tablename', 'imports')->pluck('indexname');
        $rowIndexes = DB::table('pg_indexes')->where('schemaname', 'public')->where('tablename', 'import_rows')->pluck('indexname');

        foreach (['imports_user_id_index', 'imports_status_index', 'imports_type_index', 'imports_created_at_index', 'imports_started_at_index', 'imports_finished_at_index'] as $index) {
            $this->assertContains($index, $importIndexes);
        }
        foreach (['import_rows_import_id_index', 'import_rows_status_index', 'import_rows_related_entity_type_related_entity_id_index', 'import_rows_created_at_index', 'import_rows_import_id_row_number_unique'] as $index) {
            $this->assertContains($index, $rowIndexes);
        }

        $constraints = DB::select("SELECT conname, pg_get_constraintdef(oid) AS definition FROM pg_constraint WHERE conrelid IN ('imports'::regclass, 'import_rows'::regclass)");
        $definitions = collect($constraints)->mapWithKeys(fn (object $constraint): array => [$constraint->conname => $constraint->definition]);

        $this->assertStringContainsString('ON DELETE RESTRICT', $definitions->get('imports_user_id_foreign'));
        $this->assertStringContainsString('ON DELETE CASCADE', $definitions->get('import_rows_import_id_foreign'));
    }

    public function test_import_json_columns_are_postgresql_jsonb(): void
    {
        $columns = DB::table('information_schema.columns')
            ->where('table_schema', 'public')
            ->whereIn('table_name', ['imports', 'import_rows'])
            ->whereIn('column_name', ['metadata', 'original_data', 'normalized_data', 'dedup_data', 'execution_data'])
            ->get(['table_name', 'column_name', 'data_type'])
            ->mapWithKeys(fn (object $column): array => [
                $column->table_name.'.'.$column->column_name => $column->data_type,
            ]);

        $this->assertSame('jsonb', $columns->get('imports.metadata'));
        $this->assertSame('jsonb', $columns->get('import_rows.original_data'));
        $this->assertSame('jsonb', $columns->get('import_rows.normalized_data'));
        $this->assertSame('jsonb', $columns->get('import_rows.dedup_data'));
        $this->assertSame('jsonb', $columns->get('import_rows.execution_data'));
    }
}
