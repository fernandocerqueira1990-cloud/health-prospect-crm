<?php

namespace App\Actions\Users;

use App\Models\Role;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class EnsureActiveAdministratorRemainsAction
{
    /** @param array<int, int|string> $roleIds */
    public function execute(User $user, bool $willBeActive, array $roleIds): void
    {
        $administratorRole = Role::query()
            ->where('slug', Role::ADMIN_SLUG)
            ->lockForUpdate()
            ->firstOrFail();

        $isActiveAdministrator = $user->active
            && $user->roles()->whereKey($administratorRole->id)->exists();
        $willBeAdministrator = in_array(
            $administratorRole->id,
            array_map('intval', $roleIds),
            true,
        );

        if (! $isActiveAdministrator || ($willBeActive && $willBeAdministrator)) {
            return;
        }

        $anotherActiveAdministratorExists = User::query()
            ->whereKeyNot($user->getKey())
            ->where('active', true)
            ->whereHas('roles', fn ($query) => $query->whereKey($administratorRole->id))
            ->exists();

        if (! $anotherActiveAdministratorExists) {
            throw ValidationException::withMessages([
                'active' => 'A instalação deve manter pelo menos um administrador ativo.',
                'role_ids' => 'A role Administrador não pode ser removida do último administrador ativo.',
            ]);
        }
    }
}
