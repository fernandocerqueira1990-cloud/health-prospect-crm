<?php

namespace App\Actions\Imports;

use App\Models\DataImport;
use App\Models\User;
use App\Services\AuditService;
use App\Services\CsvImportReader;
use App\Support\ImportFilename;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class CreateCsvImportAction
{
    public function __construct(private readonly CsvImportReader $reader, private readonly AuditService $audit) {}

    public function execute(UploadedFile $file, User $user): DataImport
    {
        $filename = Str::uuid()->toString().'.csv';
        $disk = Storage::disk((string) config('imports.disk'));
        $disk->putFileAs('', $file, $filename);

        try {
            $dataImport = DB::transaction(function () use ($file, $filename, $user): DataImport {
                $dataImport = DataImport::create(['user_id' => $user->id, 'filename' => $filename, 'original_filename' => ImportFilename::sanitize($file->getClientOriginalName()), 'type' => DataImport::TYPE_CSV, 'status' => DataImport::STATUS_UPLOADED, 'metadata' => []]);
                $this->audit->record('import_created', $dataImport, after: ['import_id' => $dataImport->id, 'type' => $dataImport->type, 'status' => $dataImport->status]);

                return $dataImport;
            });

            return $this->reader->parse($dataImport);
        } catch (Throwable $exception) {
            $disk->delete($filename);
            throw $exception;
        }
    }
}
