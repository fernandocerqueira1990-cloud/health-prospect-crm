<?php

namespace App\Actions\Imports;

use App\Models\DataImport;
use App\Services\AuditService;
use App\Support\ImportStoragePath;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DeleteImportAction
{
    public function __construct(private readonly AuditService $audit) {}

    public function execute(DataImport $dataImport): void
    {
        $filename = ImportStoragePath::assertSafe($dataImport);
        $disk = Storage::disk((string) config('imports.disk'));

        if ($disk->exists($filename) && ! $disk->delete($filename)) {
            throw new RuntimeException('Não foi possível remover o arquivo privado da importação.');
        }

        DB::transaction(function () use ($dataImport): void {
            $this->audit->record('import_deleted', $dataImport, before: ['import_id' => $dataImport->id, 'type' => $dataImport->type, 'status' => $dataImport->status, 'total_rows' => $dataImport->total_rows]);
            $dataImport->delete();
        });
    }
}
