<?php

namespace App\Actions\Users;

use Illuminate\Support\Facades\DB;
use LogicException;
use RuntimeException;

class NormalizeExistingUserEmailsAction
{
    public const CONSTRAINT = 'users_email_canonical_check';

    public function execute(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            throw new LogicException('A normalização de e-mails existentes requer PostgreSQL.');
        }

        DB::transaction(function (): void {
            DB::statement('LOCK TABLE users IN ACCESS EXCLUSIVE MODE');

            $collisions = DB::table('users')
                ->selectRaw('lower(btrim(email)) as canonical_email, count(*) as aggregate')
                ->groupByRaw('lower(btrim(email))')
                ->havingRaw('count(*) > 1')
                ->pluck('canonical_email')
                ->map(static fn (mixed $email): string => (string) $email)
                ->all();

            if ($collisions !== []) {
                throw new RuntimeException(
                    'Migração abortada: existem e-mails que colidem após trim/lowercase: '
                    .implode(', ', $collisions)
                    .'. Resolva manualmente as contas conflitantes e execute a migration novamente.',
                );
            }

            DB::table('users')->update([
                'email' => DB::raw('lower(btrim(email))'),
            ]);

            DB::statement(sprintf(
                'ALTER TABLE users ADD CONSTRAINT %s CHECK (email = lower(btrim(email)))',
                self::CONSTRAINT,
            ));
        });
    }
}
