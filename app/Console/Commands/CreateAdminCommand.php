<?php

namespace App\Console\Commands;

use App\Actions\Roles\EnsureAdministratorRoleAction;
use App\Actions\Users\AssignUserRolesAction;
use App\Models\User;
use App\Support\EmailNormalizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdminCommand extends Command
{
    protected $signature = 'crm:create-admin {--name=} {--email=}';

    protected $description = 'Cria com segurança o primeiro administrador do CRM';

    public function handle(
        EnsureAdministratorRoleAction $ensureAdministratorRole,
        AssignUserRolesAction $assignRoles,
    ): int {
        $name = (string) ($this->option('name') ?: $this->ask('Nome'));
        $email = EmailNormalizer::normalize((string) ($this->option('email') ?: $this->ask('E-mail')));
        $password = (string) $this->secret('Senha');
        $confirmation = (string) $this->secret('Confirme a senha');

        $validator = Validator::make(compact('name', 'email', 'password') + ['password_confirmation' => $confirmation], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        DB::transaction(function () use ($name, $email, $password, $ensureAdministratorRole, $assignRoles): void {
            $role = $ensureAdministratorRole->execute();
            $user = User::create(['name' => $name, 'email' => $email, 'password' => Hash::make($password), 'active' => true]);
            $assignRoles->execute($user, [$role->id]);
        });
        $this->info('Administrador criado com sucesso.');

        return self::SUCCESS;
    }
}
