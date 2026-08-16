<?php

namespace App\Models;

use Database\Factories\ImportRowFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property array<string, mixed> $original_data
 * @property array<string, mixed>|null $normalized_data
 * @property array<string, mixed>|null $dedup_data
 * @property array<string, mixed>|null $execution_data
 */
#[Fillable(['import_id', 'row_number', 'status', 'original_data', 'normalized_data', 'error_message', 'related_entity_type', 'related_entity_id'])]
class ImportRow extends Model
{
    /** @use HasFactory<ImportRowFactory> */
    use HasFactory;

    public const STATUS_PARSED = 'parsed';

    public const STATUS_FAILED = 'failed';

    /** @return BelongsTo<DataImport, $this> */
    public function dataImport(): BelongsTo
    {
        return $this->belongsTo(DataImport::class, 'import_id');
    }

    protected function casts(): array
    {
        return ['row_number' => 'integer', 'original_data' => 'array', 'normalized_data' => 'array', 'dedup_data' => 'array', 'execution_data' => 'array', 'related_entity_id' => 'integer'];
    }
}
