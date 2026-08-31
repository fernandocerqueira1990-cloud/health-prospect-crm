<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-slate-950">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Criar conta — CRM X</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-full items-center justify-center p-4 antialiased">
    <main class="w-full max-w-md rounded-2xl bg-white p-7 shadow-2xl sm:p-9">
        <p class="text-sm font-semibold uppercase tracking-wider text-teal-700">CRM X</p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">Crie sua conta</h1>
        <p class="mt-2 text-sm text-slate-600">Cadastre-se para testar os módulos operacionais do CRM.</p>
        <div class="mt-6"><x-errors /></div>
        <form method="POST" action="{{ route('register.store') }}" class="space-y-5">
            @csrf
            <div><label class="label" for="name">Nome</label><input class="input" id="name" name="name" type="text" value="{{ old('name') }}" required autofocus autocomplete="name"></div>
            <div><label class="label" for="email">E-mail</label><input class="input" id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username"></div>
            <div><label class="label" for="password">Senha</label><input class="input" id="password" name="password" type="password" required autocomplete="new-password"></div>
            <div><label class="label" for="password_confirmation">Confirmação de senha</label><input class="input" id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"></div>
            <button class="btn-primary w-full" type="submit">Criar conta</button>
        </form>
        <div class="mt-6 border-t border-slate-200 pt-6 text-center">
            <a class="text-sm font-semibold text-teal-700 hover:text-teal-800" href="{{ route('login') }}">Já possui uma conta? Entrar</a>
        </div>
    </main>
</body>
</html>
