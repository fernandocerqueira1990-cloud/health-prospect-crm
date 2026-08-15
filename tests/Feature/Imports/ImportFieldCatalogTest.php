<?php

namespace Tests\Feature\Imports;

use App\Support\ImportFieldCatalog;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ImportFieldCatalogTest extends TestCase
{
    public function test_catalog_contains_unique_allowed_targets_with_consistent_groups(): void
    {
        $catalog = app(ImportFieldCatalog::class);
        $targets = $catalog->targets();

        $this->assertCount(40, $targets);
        $this->assertSame($targets, array_values(array_unique($targets)));
        $this->assertSame(['company', 'contact', 'lead'], array_keys($catalog->groups()));
        $this->assertTrue($catalog->allows('company.trade_name'));
        $this->assertTrue($catalog->allows('contact.whatsapp'));
        $this->assertTrue($catalog->allows('lead.score'));
    }

    public function test_internal_relationship_targets_are_not_exposed(): void
    {
        $targets = app(ImportFieldCatalog::class)->targets();

        foreach (['company.assigned_user_id', 'company.source_id', 'contact.company_id', 'lead.company_id', 'lead.contact_id', 'lead.assigned_user_id', 'lead.source_id', 'lead.channel_id', 'lead.first_touch_source_event_id', 'lead.last_touch_source_event_id'] as $target) {
            $this->assertNotContains($target, $targets);
        }
    }

    #[DataProvider('suggestionProvider')]
    public function test_high_confidence_suggestions(string $header, ?string $expected): void
    {
        $this->assertSame($expected, app(ImportFieldCatalog::class)->suggest($header));
    }

    /** @return array<string, array{string, string|null}> */
    public static function suggestionProvider(): array
    {
        return [
            'company accents' => ['  Razão   Social ', 'company.legal_name'],
            'company punctuation' => ['Nome-da-Empresa', 'company.trade_name'],
            'tax id' => ['CNPJ', 'company.tax_id'],
            'state' => ['UF', 'company.state'],
            'contact' => ['Nome do Contato', 'contact.name'],
            'linkedin' => ['LinkedIn', 'contact.linkedin_url'],
            'ambiguous name' => ['Nome', null],
            'ambiguous phone' => ['Telefone', null],
            'ambiguous email' => ['Email', null],
            'ambiguous status' => ['Status', null],
        ];
    }
}
