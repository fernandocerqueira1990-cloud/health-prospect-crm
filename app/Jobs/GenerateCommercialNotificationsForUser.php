<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\CommercialNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateCommercialNotificationsForUser implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public int $uniqueFor = 3600;

    public function __construct(public int $userId)
    {
        $this->onQueue('commercial');
    }

    public function uniqueId(): string
    {
        return (string) $this->userId;
    }

    public function handle(CommercialNotificationService $service): void
    {
        $user = User::query()
            ->whereKey($this->userId)
            ->where('active', true)
            ->first();

        if ($user === null) {
            return;
        }

        $service->generateFor($user);
    }
}
