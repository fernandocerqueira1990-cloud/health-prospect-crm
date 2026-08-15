<?php

namespace App\Models;

use Database\Factories\DataImportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'filename', 'original_filename', 'type', 'status', 'total_rows', 'imported_rows', 'duplicate_rows', 'failed_rows', 'started_at', 'finished_at', 'metadata'])]
class DataImport extends Model
{
    /** @use HasFactory<DataImportFactory> */
    use HasFactory;

    protected $table = 'imports';

    public const TYPE_CSV = 'csv';

    public const STATUS_UPLOADED = 'uploaded';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_PARSED = 'parsed';

    public const STATUS_FAILED = 'failed';

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<ImportRow, $this> */
    public function rows(): HasMany
    {
        return $this->hasMany(ImportRow::class, 'import_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['total_rows' => 'integer', 'imported_rows' => 'integer', 'duplicate_rows' => 'integer', 'failed_rows' => 'integer', 'started_at' => 'datetime', 'finished_at' => 'datetime', 'metadata' => 'array'];
    }
}
