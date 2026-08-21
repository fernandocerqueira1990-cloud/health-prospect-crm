<?php

namespace App\Console\Commands;

use App\Jobs\GenerateCommercialNotificationsForUser;
use App\Models\User;
use Illuminate\Console\Command;

class GenerateCommercialNotifications extends Command
{
    protected $signature = 'commercial:notifications {--sync : Process alerts synchronously instead of dispatching queue jobs}';

    protected $description = 'Generate proactive commercial notifications for active users';

    public function handle(): int
    {
        $dispatched = 0;

        User::query()
            ->where('active', true)
            ->orderBy('id')
            ->select(['id'])
            ->chunkById(100, function ($users) use (&$dispatched): void {
                foreach ($users as $user) {
                    if ($this->option('sync')) {
                        GenerateCommercialNotificationsForUser::dispatchSync($user->id);
                    } else {
                        GenerateCommercialNotificationsForUser::dispatch($user->id);
                    }

                    $dispatched++;
                }
            });

        $mode = $this->option('sync') ? 'processados' : 'enfileirados';
        $this->info("{$dispatched} usuário(s) {$mode} para geração de alertas comerciais.");

        return self::SUCCESS;
    }
}
