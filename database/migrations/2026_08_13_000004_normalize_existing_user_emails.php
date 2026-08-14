<?php

use App\Actions\Users\NormalizeExistingUserEmailsAction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        app(NormalizeExistingUserEmailsAction::class)->execute();
    }

    public function down(): void
    {
        DB::statement(sprintf(
            'ALTER TABLE users DROP CONSTRAINT IF EXISTS %s',
            NormalizeExistingUserEmailsAction::CONSTRAINT,
        ));
    }
};
