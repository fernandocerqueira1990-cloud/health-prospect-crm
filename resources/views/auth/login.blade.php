<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-slate-950">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Entrar — Health Prospect CRM</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-full items-center justify-center p-4 antialiased">
    <main class="w-full max-w-md rounded-2xl bg-white p-7 shadow-2xl sm:p-9">
        <p class="text-sm font-semibold uppercase tracking-wider text-teal-700">Health Prospect CRM</p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">Acesse sua conta</h1>
        <p class="mt-2 text-sm text-slate-600">Entre com suas credenciais corporativas.</p>
        <div class="mt-6"><x-errors /></div>
        <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
            @csrf
            <div><label class="label" for="email">E-mail</label><input class="input" id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"></div>
            <div><label class="label" for="password">Senha</label><input class="input" id="password" name="password" type="password" required autocomplete="current-password"></div>
            <label class="flex items-center gap-2 text-sm text-slate-700"><input class="rounded border-slate-300" name="remember" type="checkbox" value="1" @checked(old('remember'))> Lembrar de mim</label>
            <button class="btn-primary w-full" type="submit">Entrar</button>
        </form>
        <div class="mt-6 border-t border-slate-200 pt-6 text-center">
            <p class="text-sm text-slate-600">Ainda não tem uma conta?</p>
            <a class="mt-1 inline-block text-sm font-semibold text-teal-700 hover:text-teal-800" href="{{ route('register') }}">Criar uma conta para testar</a>
        </div>
    </main>
</body>
</html>
