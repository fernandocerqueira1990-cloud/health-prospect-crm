<x-errors />
<div class="grid gap-5 sm:grid-cols-2"><div><label class="label" for="name">Nome</label><input class="input" id="name" name="name" value="{{ old('name', $role?->name) }}" required></div><div><label class="label" for="slug">Slug</label><input class="input" id="slug" name="slug" value="{{ old('slug', $role?->slug) }}" pattern="[a-z0-9_]+" required></div></div>
<div class="mt-5"><label class="label" for="description">Descrição</label><textarea class="input" id="description" name="description" rows="3">{{ old('description', $role?->description) }}</textarea></div>
<div class="mt-5"><input type="hidden" name="active" value="0"><label class="flex items-center gap-2 text-sm font-medium"><input type="checkbox" name="active" value="1" @checked((bool) old('active', $role?->active ?? true))> Role ativa</label></div>
@can('roles.manage_permissions')
    <input type="hidden" name="permission_ids[]" value="">
    <fieldset class="mt-7"><legend class="text-base font-semibold">Permissões</legend><div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($permissions as $permission)<label class="flex items-center gap-2 rounded-lg border border-slate-200 p-3 text-sm"><input type="checkbox" name="permission_ids[]" value="{{ $permission->id }}" @checked(in_array($permission->id, old('permission_ids', $role?->permissions->pluck('id')->all() ?? []))) {{ $role?->slug === 'admin' ? 'disabled' : '' }}> <code>{{ $permission->slug }}</code></label>@endforeach
    </div>@if($role?->slug === 'admin')<p class="mt-2 text-sm text-slate-500">O Administrador sempre recebe todas as permissões.</p>@endif</fieldset>
@endcan
<div class="mt-7 flex gap-3"><button class="btn-primary" type="submit">Salvar</button><a class="btn-secondary" href="{{ route('admin.roles.index') }}">Cancelar</a></div>
