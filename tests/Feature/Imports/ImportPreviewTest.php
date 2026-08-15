<?php

namespace Tests\Feature\Imports;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Contact;
use App\Models\DataImport;
use App\Models\ImportRow;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithRbac;
use Tests\TestCase;

class ImportPreviewTest extends TestCase
{
    use InteractsWithRbac, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, PermissionSeeder::class, RolePermissionSeeder::class]);
    }

    public function test_user_with_view_permission_can_access_mapped_preview_and_user_without_it_cannot(): void
    {
        $dataImport = $this->mappedImport(['Empresa' => 'company.legal_name'], [
            [['Empresa' => 'Clínica'], ['company' => ['legal_name' => 'Clínica']]],
        ]);

        $this->actingAs($this->userWithPermission('imports.view'))->get(route('imports.preview', $dataImport))->assertOk()->assertSee('Preview da importação');
        $this->actingAs(User::factory()->create())->get(route('imports.preview', $dataImport))->assertForbidden();
    }

    public function test_failed_import_is_rejected_and_import_without_mapping_is_redirected_with_clear_message(): void
    {
        $failed = $this->mappedImport(['Empresa' => 'company.legal_name'], [], DataImport::STATUS_FAILED);
        $withoutMapping = DataImport::factory()->create(['status' => DataImport::STATUS_PARSED, 'metadata' => ['header' => ['Empresa']]]);
        ImportRow::factory()->for($withoutMapping, 'dataImport')->create(['normalized_data' => null]);
        $user = $this->userWithPermission('imports.view');

        $this->actingAs($user)->get(route('imports.preview', $failed))->assertStatus(409);
        $this->actingAs($user)->get(route('imports.preview', $withoutMapping))
            ->assertRedirect(route('imports.show', $withoutMapping))
            ->assertSessionHas('status', 'Mapeie as colunas antes de visualizar o Preview.');
    }

    public function test_incomplete_or_unlisted_mapping_contract_is_rejected(): void
    {
        $dataImport = $this->mappedImport(['Empresa' => 'company.legal_name'], [
            [['Empresa' => 'Clínica'], ['company' => ['legal_name' => 'Clínica']]],
        ]);
        $metadata = $dataImport->metadata;
        $metadata['mapping']['columns'] = ['Empresa' => 'company.assigned_user_id'];
        $dataImport->update(['metadata' => $metadata]);

        $this->actingAs($this->userWithPermission('imports.view'))->get(route('imports.preview', $dataImport))
            ->assertRedirect(route('imports.show', $dataImport))
            ->assertSessionHas('status', 'Mapeie as colunas antes de visualizar o Preview.');
    }

    public function test_preview_escapes_xss_displays_formula_as_text_and_is_completely_read_only(): void
    {
        $original = ['Empresa' => '<script>alert(1)</script>', 'Nota' => '=HYPERLINK("https://evil.test")', 'Imagem' => '<img src=x onerror=alert(1)>'];
        $normalized = ['company' => ['legal_name' => '<script>alert(1)</script>', 'notes' => '=HYPERLINK("https://evil.test") <img src=x onerror=alert(1)>']];
        $dataImport = $this->mappedImport(['Empresa' => 'company.legal_name', 'Nota' => 'company.notes'], [[$original, $normalized]]);
        $row = $dataImport->rows()->sole();
        $before = [
            'companies' => Company::query()->count(), 'contacts' => Contact::query()->count(),
            'leads' => Lead::query()->count(), 'opportunities' => Opportunity::query()->count(),
            'audits' => AuditLog::query()->count(),
        ];

        $response = $this->actingAs($this->userWithPermission('imports.view'))->get(route('imports.preview', $dataImport));

        $response->assertOk();
        $response->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        $response->assertSee('&lt;img src=x onerror=alert(1)&gt;', false)->assertDontSee('<img src=x onerror=alert(1)>', false);
        $response->assertSee('=HYPERLINK(&quot;https://evil.test&quot;)', false);
        $row->refresh();
        $dataImport->refresh();
        $this->assertEqualsCanonicalizing($original, $row->original_data);
        $this->assertEqualsCanonicalizing($normalized, $row->normalized_data);
        $this->assertNull($row->related_entity_type);
        $this->assertNull($row->related_entity_id);
        $this->assertSame(0, $dataImport->imported_rows);
        $this->assertSame(0, $dataImport->duplicate_rows);
        $this->assertSame(0, $dataImport->failed_rows);
        $this->assertSame($before, [
            'companies' => Company::query()->count(), 'contacts' => Contact::query()->count(),
            'leads' => Lead::query()->count(), 'opportunities' => Opportunity::query()->count(),
            'audits' => AuditLog::query()->count(),
        ]);
    }

    public function test_preview_filters_and_counts_valid_warning_and_error_rows(): void
    {
        $dataImport = $this->mappedImport([
            'Empresa' => 'company.legal_name', 'Email' => 'company.email', 'Telefone' => 'company.phone',
        ], [
            [['Empresa' => 'Registro Alfa', 'Email' => 'ok@example.test', 'Telefone' => '+5571999999999'], ['company' => ['legal_name' => 'Registro Alfa', 'email' => 'ok@example.test', 'phone' => '+5571999999999']]],
            [['Empresa' => 'Registro Beta', 'Email' => null, 'Telefone' => '123'], ['company' => ['legal_name' => 'Registro Beta', 'phone' => '123']]],
            [['Empresa' => 'Registro Gama', 'Email' => 'inválido', 'Telefone' => null], ['company' => ['legal_name' => 'Registro Gama', 'email' => 'inválido']]],
        ]);
        $user = $this->userWithPermission('imports.view');

        $all = $this->actingAs($user)->get(route('imports.preview', $dataImport));
        $all->assertOk()->assertSeeInOrder(['Total', '3', 'Válidos', '1', 'Avisos', '1', 'Erros', '1']);
        $this->actingAs($user)->get(route('imports.preview', [$dataImport, 'status' => 'valid']))->assertSee('Registro Alfa')->assertDontSee('Registro Beta')->assertDontSee('Registro Gama');
        $this->actingAs($user)->get(route('imports.preview', [$dataImport, 'status' => 'warning']))->assertSee('Registro Beta')->assertDontSee('Registro Alfa')->assertDontSee('Registro Gama');
        $this->actingAs($user)->get(route('imports.preview', [$dataImport, 'status' => 'error']))->assertSee('Registro Gama')->assertDontSee('Registro Alfa')->assertDontSee('Registro Beta');
    }

    public function test_preview_paginates_in_row_number_order_with_allowed_page_size(): void
    {
        $rows = [];
        for ($index = 1; $index <= 27; $index++) {
            $name = sprintf('Empresa-%02d', $index);
            $rows[] = [['Empresa' => $name], ['company' => ['legal_name' => $name]]];
        }
        $dataImport = $this->mappedImport(['Empresa' => 'company.legal_name'], $rows);
        $user = $this->userWithPermission('imports.view');

        $first = $this->actingAs($user)->get(route('imports.preview', [$dataImport, 'per_page' => 25]));
        $first->assertOk()->assertSee('>Empresa-01</p>', false)->assertSee('>Empresa-25</p>', false)->assertDontSee('>Empresa-26</p>', false);
        $second = $this->actingAs($user)->get(route('imports.preview', [$dataImport, 'per_page' => 25, 'page' => 2]));
        $second->assertOk()->assertSeeInOrder(['>Empresa-26</p>', '>Empresa-27</p>'], false)->assertDontSee('>Empresa-25</p>', false);
        $this->actingAs($user)->get(route('imports.preview', [$dataImport, 'per_page' => 26]))->assertSessionHasErrors('per_page');
    }

    /**
     * @param  array<string, string>  $mapping
     * @param  list<array{array<string, mixed>, array<string, mixed>}>  $rows
     */
    private function mappedImport(array $mapping, array $rows, string $status = DataImport::STATUS_PARSED): DataImport
    {
        $dataImport = DataImport::factory()->create([
            'status' => $status,
            'total_rows' => count($rows),
            'metadata' => [
                'header' => array_keys($mapping),
                'mapping' => ['version' => 1, 'columns' => $mapping, 'ignored_columns' => []],
            ],
        ]);
        foreach ($rows as $index => [$original, $normalized]) {
            ImportRow::factory()->for($dataImport, 'dataImport')->create([
                'row_number' => $index + 2,
                'original_data' => $original,
                'normalized_data' => $normalized,
                'related_entity_type' => null,
                'related_entity_id' => null,
            ]);
        }

        return $dataImport;
    }
}
