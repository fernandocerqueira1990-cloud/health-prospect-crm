<?php

namespace Tests\Feature\Imports;

use App\Models\AuditLog;
use App\Models\DataImport;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\Concerns\InteractsWithRbac;
use Tests\TestCase;

class XlsxImportTest extends TestCase
{
    use InteractsWithRbac, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, PermissionSeeder::class, RolePermissionSeeder::class]);
        Storage::fake('imports');
    }

    public function test_xlsx_is_stored_privately_and_parsed_with_physical_row_numbers(): void
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Clientes');
        $sheet->fromArray([['Nome', 'Cidade'], ['Clínica Saúde', 'São Paulo'], [null, null], ['Hospital', 'Recife']]);

        $dataImport = $this->upload($spreadsheet, '../../clientes.xlsx');

        $this->assertSame(DataImport::TYPE_XLSX, $dataImport->type);
        $this->assertSame(DataImport::STATUS_PARSED, $dataImport->status);
        $this->assertSame(2, $dataImport->total_rows);
        $this->assertSame(['sheet' => 'Clientes', 'header' => ['Nome', 'Cidade']], $dataImport->metadata);
        $this->assertSame([2, 4], $dataImport->rows()->orderBy('row_number')->pluck('row_number')->all());
        $this->assertSame(['Nome' => 'Clínica Saúde', 'Cidade' => 'São Paulo'], $dataImport->rows()->orderBy('row_number')->first()->original_data);
        $this->assertNull($dataImport->rows()->orderBy('row_number')->first()->normalized_data);
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}\.xlsx$/', $dataImport->filename);
        $this->assertSame('clientes.xlsx', $dataImport->original_filename);
        Storage::disk('imports')->assertExists($dataImport->filename);
        Storage::disk('public')->assertMissing($dataImport->filename);
        $this->assertDatabaseHas('audit_logs', ['action' => 'import_created', 'auditable_id' => $dataImport->id]);
    }

    public function test_formula_is_not_calculated_during_import(): void
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray([['Nome', 'Valor'], ['Teste', '=1+1']]);
        $spreadsheet->getActiveSheet()->getCell('B2')->setCalculatedValue(2);

        $dataImport = $this->upload($spreadsheet);

        $this->assertSame('=1+1', $dataImport->rows()->first()->original_data['Valor']);
    }

    public function test_duplicate_or_empty_header_fails_without_partial_rows(): void
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray([[' Nome ', 'nome'], ['Segredo', 'não auditar']]);

        $dataImport = $this->upload($spreadsheet);

        $this->assertFailed($dataImport, 'invalid_header');
    }

    public function test_only_first_worksheet_is_processed_when_multiple_are_filled(): void
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->setTitle('Primeira')->fromArray([['Nome'], ['Ana']]);
        $spreadsheet->createSheet()->fromArray([['Nome'], ['Bruno']]);

        $dataImport = $this->upload($spreadsheet);

        $this->assertSame(DataImport::STATUS_PARSED, $dataImport->status);
        $this->assertSame('Primeira', $dataImport->metadata['sheet']);
        $this->assertSame(1, $dataImport->total_rows);
        $this->assertSame('Ana', $dataImport->rows()->sole()->original_data['Nome']);
    }

    public function test_empty_first_worksheet_does_not_fall_through_to_filled_second_worksheet(): void
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->setTitle('Vazia');
        $spreadsheet->createSheet()->setTitle('Preenchida')->fromArray([['Nome'], ['Bruno']]);

        $this->assertFailed($this->upload($spreadsheet), 'invalid_header');
    }

    public function test_hidden_first_worksheet_is_still_processed(): void
    {
        $spreadsheet = new Spreadsheet;
        $first = $spreadsheet->getActiveSheet();
        $first->setTitle('Oculta')->fromArray([['Nome'], ['Ana']]);
        $first->setSheetState(Worksheet::SHEETSTATE_HIDDEN);
        $spreadsheet->createSheet()->setTitle('Visível')->fromArray([['Nome'], ['Bruno']]);

        $dataImport = $this->upload($spreadsheet);

        $this->assertSame(DataImport::STATUS_PARSED, $dataImport->status);
        $this->assertSame('Oculta', $dataImport->metadata['sheet']);
        $this->assertSame('Ana', $dataImport->rows()->sole()->original_data['Nome']);
    }

    public function test_worksheet_over_configured_row_limit_fails_before_persisting_rows(): void
    {
        config(['imports.xlsx_max_rows' => 1]);
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray([['Nome'], ['Ana']]);

        $this->assertFailed($this->upload($spreadsheet), 'worksheet_too_large');
    }

    public function test_worksheet_over_configured_column_limit_fails_before_persisting_rows(): void
    {
        config(['imports.xlsx_max_columns' => 1]);
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray([['Nome', 'Cidade'], ['Ana', 'Recife']]);

        $this->assertFailed($this->upload($spreadsheet), 'worksheet_too_large');
    }

    public function test_worksheet_inside_configured_limits_is_parsed(): void
    {
        config(['imports.xlsx_max_rows' => 2, 'imports.xlsx_max_columns' => 2]);
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray([['Nome', 'Cidade'], ['Ana', 'Recife']]);

        $dataImport = $this->upload($spreadsheet);

        $this->assertSame(DataImport::STATUS_PARSED, $dataImport->status);
        $this->assertSame(1, $dataImport->total_rows);
    }

    public function test_archive_over_uncompressed_limit_fails_without_persisting_rows(): void
    {
        config(['imports.xlsx_max_uncompressed_bytes' => 1]);
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray([['Nome'], ['Ana']]);

        $this->assertFailed($this->upload($spreadsheet), 'archive_too_large');
    }

    public function test_staging_preserves_scalar_types_and_business_like_strings(): void
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray([
            ['Código', 'Documento', 'CEP', 'Telefone', 'Data', 'Moeda', 'Booleano', 'Zero', 'Nulo'],
            ['00123', '00000123456789', '01234-567', '+55 71 99999-9999', '01/08/2026', '1.234,56', false, 0, null],
        ], null, 'A1', true);

        $data = $this->upload($spreadsheet)->rows()->sole()->original_data;

        $this->assertSame('00123', $data['Código']);
        $this->assertSame('00000123456789', $data['Documento']);
        $this->assertSame('01234-567', $data['CEP']);
        $this->assertSame('+55 71 99999-9999', $data['Telefone']);
        $this->assertSame('01/08/2026', $data['Data']);
        $this->assertSame('1.234,56', $data['Moeda']);
        $this->assertFalse($data['Booleano']);
        $this->assertSame(0, $data['Zero']);
        $this->assertNull($data['Nulo']);
    }

    public function test_invalid_zip_with_admitted_test_mime_is_recorded_as_sanitized_failure(): void
    {
        $response = $this->actingAs($this->admin())->post(route('imports.store'), [
            'file' => UploadedFile::fake()->createWithContent('dados.xlsx', 'SEGREDO arquivo inválido'),
        ]);

        $dataImport = DataImport::query()->sole();
        $response->assertRedirect(route('imports.show', $dataImport));
        $this->assertFailed($dataImport, 'invalid_archive');
        $audit = AuditLog::query()->where('action', 'import_failed')->firstOrFail();
        $this->assertStringNotContainsString('SEGREDO', json_encode($audit->after, JSON_THROW_ON_ERROR));
    }

    public function test_legacy_xls_extension_is_rejected(): void
    {
        $this->actingAs($this->admin())->post(route('imports.store'), [
            'file' => UploadedFile::fake()->create('dados.xls', 1, 'application/vnd.ms-excel'),
        ])->assertSessionHasErrors('file');

        $this->assertDatabaseCount('imports', 0);
    }

    public function test_unsupported_spreadsheet_extensions_are_rejected(): void
    {
        foreach (['dados.xlsm', 'dados.ods', 'dados.exe', 'dados.zip'] as $name) {
            $this->actingAs($this->admin())->post(route('imports.store'), [
                'file' => UploadedFile::fake()->create($name, 1),
            ])->assertSessionHasErrors('file');
        }

        $this->assertDatabaseCount('imports', 0);
    }

    public function test_plain_text_renamed_to_xlsx_is_rejected_by_real_mime_detection(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'fake-xlsx-');
        $this->assertNotFalse($path);
        file_put_contents($path, 'arquivo executável ou texto arbitrário');

        try {
            $file = new UploadedFile($path, 'falso.xlsx', null, null, true);
            $this->actingAs($this->admin())->post(route('imports.store'), ['file' => $file])->assertSessionHasErrors('file');
        } finally {
            @unlink($path);
        }

        $this->assertDatabaseCount('imports', 0);
    }

    private function upload(Spreadsheet $spreadsheet, string $name = 'dados.xlsx'): DataImport
    {
        $path = tempnam(sys_get_temp_dir(), 'xlsx-test-');
        $this->assertNotFalse($path);
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        try {
            $file = new UploadedFile($path, $name, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
            $this->actingAs($this->admin())->post(route('imports.store'), ['file' => $file])->assertSessionHasNoErrors();
        } finally {
            @unlink($path);
        }

        return DataImport::query()->latest('id')->firstOrFail();
    }

    private function assertFailed(DataImport $dataImport, string $reason): void
    {
        $this->assertSame(DataImport::STATUS_FAILED, $dataImport->status);
        $this->assertSame($reason, $dataImport->metadata['error_code']);
        $this->assertDatabaseCount('import_rows', 0);
        $this->assertDatabaseHas('audit_logs', ['action' => 'import_failed', 'auditable_id' => $dataImport->id]);
    }
}
