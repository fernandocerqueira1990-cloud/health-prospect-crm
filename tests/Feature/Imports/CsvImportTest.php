<?php

namespace Tests\Feature\Imports;

use App\Actions\Imports\DeleteImportAction;
use App\Models\AuditLog;
use App\Models\DataImport;
use App\Models\ImportRow;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Tests\Concerns\InteractsWithRbac;
use Tests\TestCase;

class CsvImportTest extends TestCase
{
    use InteractsWithRbac, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, PermissionSeeder::class, RolePermissionSeeder::class]);
        Storage::fake('imports');
    }

    public function test_comma_csv_with_utf8_bom_quotes_and_empty_lines_is_parsed(): void
    {
        $csv = "\xEF\xBB\xBFNome,Observação\n\"Clínica Saúde\",\"Tem vírgula, no campo\"\n\nHospital,São Paulo\n";
        $dataImport = $this->upload($csv, 'clientes.csv');

        $this->assertSame(DataImport::STATUS_PARSED, $dataImport->status);
        $this->assertSame(2, $dataImport->total_rows);
        $this->assertSame(',', $dataImport->metadata['delimiter']);
        $this->assertSame([2, 4], $dataImport->rows()->orderBy('row_number')->pluck('row_number')->all());
        $this->assertSame('Tem vírgula, no campo', $dataImport->rows()->first()->original_data['Observação']);
        $this->assertSame('São Paulo', $dataImport->rows()->orderByDesc('id')->first()->original_data['Observação']);
    }

    public function test_semicolon_csv_is_detected(): void
    {
        $dataImport = $this->upload("Nome;Cidade\nAna;Curitiba\n", 'pessoas.csv');
        $this->assertSame(';', $dataImport->metadata['delimiter']);
        $this->assertSame(['Nome' => 'Ana', 'Cidade' => 'Curitiba'], $dataImport->rows()->first()->original_data);
    }

    public function test_doubled_quotes_inside_quoted_field_are_preserved(): void
    {
        $dataImport = $this->upload("Nome,Observação\n\"Clínica \"\"Vida\"\"\",ok\n", 'aspas.csv');

        $this->assertSame(DataImport::STATUS_PARSED, $dataImport->status);
        $this->assertSame('Clínica "Vida"', $dataImport->rows()->first()->original_data['Nome']);
    }

    public function test_tab_csv_is_detected(): void
    {
        $dataImport = $this->upload("Nome\tCidade\nAna\tRecife\n", 'pessoas.csv');
        $this->assertSame('tab', $dataImport->metadata['delimiter']);
        $this->assertSame(1, $dataImport->total_rows);
    }

    public function test_inconsistent_csv_fails_without_persisting_partial_rows_or_sensitive_content_in_audit(): void
    {
        $secret = 'SEGREDO-NAO-AUDITAR';
        $dataImport = $this->upload("Nome,Email\nAna,$secret\nLinha,com,colunas\n", 'falha.csv');

        $this->assertSame(DataImport::STATUS_FAILED, $dataImport->status);
        $this->assertDatabaseCount('import_rows', 0);
        $this->assertDatabaseHas('audit_logs', ['action' => 'import_failed', 'auditable_id' => $dataImport->id]);
        $auditData = json_encode(AuditLog::query()->where('action', 'import_failed')->firstOrFail()->after, JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString($secret, $auditData);
    }

    public function test_row_with_fewer_columns_fails(): void
    {
        $this->assertFailedImport("Nome,Email\nAna\n", 'column_count_mismatch');
    }

    public function test_row_with_more_columns_fails(): void
    {
        $this->assertFailedImport("Nome,Email\nAna,ana@example.com,extra\n", 'column_count_mismatch');
    }

    public function test_empty_header_fails(): void
    {
        $this->assertFailedImport(",\nAna,ana@example.com\n", 'invalid_header');
    }

    public function test_duplicate_header_after_normalization_fails(): void
    {
        $this->assertFailedImport(" Nome ,nome\nAna,Outra\n", 'invalid_header');
    }

    public function test_unclosed_quoted_field_fails(): void
    {
        $this->assertFailedImport("Nome,Email\nAna,\"sem fechamento\n", 'malformed_csv');
    }

    public function test_multiline_field_and_empty_line_preserve_physical_start_rows(): void
    {
        $dataImport = $this->upload("Nome,Observação\nAna,\"linha um\nlinha dois\"\n\nBruno,ok\n", 'multilinha.csv');

        $this->assertSame(DataImport::STATUS_PARSED, $dataImport->status);
        $this->assertSame([2, 5], $dataImport->rows()->orderBy('row_number')->pluck('row_number')->all());
        $this->assertSame("linha um\nlinha dois", $dataImport->rows()->orderBy('row_number')->first()->original_data['Observação']);
    }

    public function test_valid_upload_uses_private_disk_and_safe_internal_filename(): void
    {
        $dataImport = $this->upload("Nome,Email\nAna,ana@example.com\n", '../../clientes.csv');

        Storage::disk('imports')->assertExists($dataImport->filename);
        Storage::disk('public')->assertMissing($dataImport->filename);
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}\.csv$/', $dataImport->filename);
        $this->assertSame('clientes.csv', $dataImport->original_filename);
        $this->assertDatabaseHas('audit_logs', ['action' => 'import_created', 'auditable_id' => $dataImport->id]);
        $this->assertSame(1, ImportRow::query()->count());
    }

    public function test_windows_path_is_removed_from_original_filename(): void
    {
        $dataImport = $this->upload("Nome,Email\nAna,ana@example.com\n", 'C:\\temp\\arquivo.csv');

        $this->assertSame('arquivo.csv', $dataImport->original_filename);
    }

    public function test_long_original_filename_is_limited_and_keeps_csv_extension(): void
    {
        $dataImport = $this->upload("Nome,Email\nAna,ana@example.com\n", str_repeat('a', 300).'.csv');

        $this->assertSame(255, mb_strlen($dataImport->original_filename));
        $this->assertStringEndsWith('.csv', $dataImport->original_filename);
    }

    public function test_control_characters_are_removed_from_original_filename(): void
    {
        $dataImport = $this->upload("Nome,Email\nAna,ana@example.com\n", "arquivo\x00\x1F.csv");

        $this->assertSame('arquivo.csv', $dataImport->original_filename);
    }

    public function test_invalid_extension_is_rejected(): void
    {
        $this->actingAs($this->admin())->post(route('imports.store'), ['file' => UploadedFile::fake()->createWithContent('dados.txt', "Nome,Email\nAna,a@b.test\n")])->assertSessionHasErrors('file');
        $this->assertDatabaseCount('imports', 0);
    }

    public function test_dangerous_double_extension_is_rejected(): void
    {
        $this->actingAs($this->admin())->post(route('imports.store'), [
            'file' => UploadedFile::fake()->createWithContent('payload.php.csv', "Nome,Email\nAna,a@b.test\n"),
        ])->assertSessionHasErrors('file');

        $this->assertDatabaseCount('imports', 0);
    }

    public function test_csv_formula_like_cells_are_preserved_as_inert_domain_data(): void
    {
        $dataImport = $this->upload("Nome,Observação\nAna,=1+1\n", 'formula.csv');

        $this->assertSame('=1+1', $dataImport->rows()->sole()->original_data['Observação']);
    }

    public function test_oversized_csv_record_fails_without_partial_rows(): void
    {
        config(['imports.csv_max_record_bytes' => 1024]);

        $this->assertFailedImport("Nome,Observação\nAna,".str_repeat('x', 1100)."\n", 'record_too_large');
    }

    public function test_formula_and_control_character_headers_are_rejected(): void
    {
        $this->assertFailedImport("=cmd,Email\nAna,a@b.test\n", 'invalid_header');
    }

    public function test_invalid_mime_is_rejected(): void
    {
        $file = UploadedFile::fake()->create('dados.csv', 1, 'image/png');
        $this->actingAs($this->admin())->post(route('imports.store'), ['file' => $file])->assertSessionHasErrors('file');
        $this->assertDatabaseCount('imports', 0);
    }

    public function test_oversized_file_is_rejected(): void
    {
        config(['imports.max_upload_kb' => 1]);
        $this->actingAs($this->admin())->post(route('imports.store'), ['file' => UploadedFile::fake()->create('dados.csv', 2, 'text/csv')])->assertSessionHasErrors('file');
        $this->assertDatabaseCount('imports', 0);
    }

    public function test_authorized_delete_removes_private_file_rows_and_records_audit(): void
    {
        $dataImport = $this->upload("Nome,Email\nAna,ana@example.com\n", 'dados.csv');

        $this->actingAs($this->admin())->delete(route('imports.destroy', $dataImport))->assertRedirect(route('imports.index'));

        Storage::disk('imports')->assertMissing($dataImport->filename);
        $this->assertDatabaseMissing('imports', ['id' => $dataImport->id]);
        $this->assertDatabaseCount('import_rows', 0);
        $this->assertDatabaseHas('audit_logs', ['action' => 'import_deleted', 'auditable_id' => $dataImport->id]);
    }

    public function test_delete_succeeds_when_private_file_is_already_missing(): void
    {
        $dataImport = $this->upload("Nome,Email\nAna,ana@example.com\n", 'dados.csv');
        Storage::disk('imports')->delete($dataImport->filename);

        $this->actingAs($this->admin())->delete(route('imports.destroy', $dataImport))->assertRedirect(route('imports.index'));

        $this->assertDatabaseMissing('imports', ['id' => $dataImport->id]);
        $this->assertDatabaseCount('import_rows', 0);
        $this->assertDatabaseHas('audit_logs', ['action' => 'import_deleted', 'auditable_id' => $dataImport->id]);
    }

    public function test_filesystem_failure_keeps_database_record_and_rows_for_retry(): void
    {
        $filename = '550e8400-e29b-41d4-a716-446655440000.csv';
        $dataImport = DataImport::factory()->create(['filename' => $filename]);
        ImportRow::factory()->for($dataImport, 'dataImport')->create();
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('exists')->once()->with($filename)->andReturnTrue();
        $disk->shouldReceive('delete')->once()->with($filename)->andReturnFalse();
        Storage::shouldReceive('disk')->once()->with('imports')->andReturn($disk);

        try {
            app(DeleteImportAction::class)->execute($dataImport);
            $this->fail('A falha do filesystem deveria interromper a exclusão.');
        } catch (RuntimeException) {
            $this->assertDatabaseHas('imports', ['id' => $dataImport->id]);
            $this->assertDatabaseHas('import_rows', ['import_id' => $dataImport->id]);
            $this->assertDatabaseMissing('audit_logs', ['action' => 'import_deleted', 'auditable_id' => $dataImport->id]);
        }
    }

    private function upload(string $content, string $name): DataImport
    {
        $response = $this->actingAs($this->admin())->post(route('imports.store'), ['file' => UploadedFile::fake()->createWithContent($name, $content)]);
        $response->assertRedirect();

        return DataImport::query()->latest('id')->firstOrFail();
    }

    private function assertFailedImport(string $content, string $errorCode): void
    {
        $dataImport = $this->upload($content, 'falha.csv');

        $this->assertSame(DataImport::STATUS_FAILED, $dataImport->status);
        $this->assertSame($errorCode, $dataImport->metadata['error_code']);
        $this->assertDatabaseCount('import_rows', 0);
    }
}
