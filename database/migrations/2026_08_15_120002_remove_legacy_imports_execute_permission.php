<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const LEGACY_SLUG = 'imports.execute';

    public function up(): void
    {
        DB::transaction(function (): void {
            $permissionId = DB::table('permissions')
                ->where('slug', self::LEGACY_SLUG)
                ->value('id');

            if ($permissionId === null) {
                return;
            }

            DB::table('permission_role')
                ->where('permission_id', $permissionId)
                ->delete();

            DB::table('permissions')
                ->where('id', $permissionId)
                ->delete();
        });
    }

    public function down(): void
    {
        DB::table('permissions')->updateOrInsert(
            ['slug' => self::LEGACY_SLUG],
            [
                'name' => 'Imports Execute',
                'description' => 'Permissão legada de execução de importações.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }
};
