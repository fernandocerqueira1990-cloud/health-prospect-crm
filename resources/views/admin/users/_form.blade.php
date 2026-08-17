<x-errors />
<div class="grid gap-5 sm:grid-cols-2">
    <div><label class="label" for="name">Nome</label><input class="input" id="name" name="name" value="{{ old('name', $managedUser?->name) }}" required></div>
    <div><label class="label" for="email">E-mail</label><input class="input" id="email" name="email" type="email" value="{{ old('email', $managedUser?->email) }}" required></div>
    <div><label class="label" for="password">Senha {{ $managedUser ? '(deixe em branco para manter)' : '' }}</label><input class="input" id="password" name="password" type="password" {{ $managedUser ? '' : 'required' }} autocomplete="new-password"></div>
    <div><label class="label" for="password_confirmation">Confirmar senha</label><input class="input" id="password_confirmation" name="password_confirmation" type="password" {{ $managedUser ? '' : 'required' }} autocomplete="new-password"></div>
</div>
<div class="mt-5"><input type="hidden" name="active" value="0"><label class="flex items-center gap-2 text-sm font-medium"><input type="checkbox" name="active" value="1" @checked((bool) old('active', $managedUser?->active ?? true))> Usuário ativo</label></div>
@can('users.manage_roles')
    <input type="hidden" name="role_ids[]" value="">
    <fieldset class="mt-7"><legend class="text-base font-semibold">Roles</legend><div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($roles as $role)<label class="flex items-center gap-2 rounded-lg border border-slate-200 p-3 text-sm"><input type="checkbox" name="role_ids[]" value="{{ $role->id }}" @checked(in_array($role->id, old('role_ids', $managedUser?->roles->pluck('id')->all() ?? [])))> {{ $role->name }}</label>@endforeach
    </div></fieldset>
@endcan
<div class="mt-7 flex gap-3"><button class="btn-primary" type="submit">Salvar</button><a class="btn-secondary" href="{{ route('admin.users.index') }}">Cancelar</a></div>
