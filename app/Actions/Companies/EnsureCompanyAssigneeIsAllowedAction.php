<?php

namespace App\Actions\Companies;

use App\Models\User;
use Illuminate\Validation\ValidationException;

class EnsureCompanyAssigneeIsAllowedAction
{
    public function execute(?int $assignedUserId, ?int $currentAssignedUserId = null): void
    {
        if ($assignedUserId === null || $assignedUserId === $currentAssignedUserId) {
            return;
        }

        $activeUser = User::query()
            ->whereKey($assignedUserId)
            ->where('active', true)
            ->lockForUpdate()
            ->first();

        if ($activeUser === null) {
            throw ValidationException::withMessages([
                'assigned_user_id' => __('O responsável selecionado deve existir e estar ativo.'),
            ]);
        }
    }
}
