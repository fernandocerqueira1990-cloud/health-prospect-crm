<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE imports DROP CONSTRAINT imports_type_check');
        DB::statement("ALTER TABLE imports ADD CONSTRAINT imports_type_check CHECK (type IN ('csv', 'xlsx'))");
    }

    public function down(): void
    {
        if (DB::table('imports')->where('type', 'xlsx')->exists()) {
            throw new RuntimeException('Não é possível reverter: existem importações XLSX.');
        }

        DB::statement('ALTER TABLE imports DROP CONSTRAINT imports_type_check');
        DB::statement("ALTER TABLE imports ADD CONSTRAINT imports_type_check CHECK (type IN ('csv'))");
    }
};
