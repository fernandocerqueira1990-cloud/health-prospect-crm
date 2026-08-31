<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-950 text-slate-100 antialiased">
        <main class="mx-auto flex min-h-screen max-w-5xl items-center px-6 py-16">
            <section class="max-w-2xl">
                <p class="mb-4 text-sm font-semibold uppercase tracking-[0.2em] text-emerald-400">
                    Núcleo Laravel
                </p>
                <h1 class="text-4xl font-semibold tracking-tight sm:text-6xl">
                    CRM X
                </h1>
                <p class="mt-6 text-lg leading-8 text-slate-300">
                    Fundação self-hosted preparada com Blade, Tailwind CSS, PostgreSQL e Redis.
                </p>
                <p class="mt-8 inline-flex rounded-full border border-slate-700 px-4 py-2 text-sm text-slate-400">
                    Sprint 0 · infraestrutura inicial
                </p>
            </section>
        </main>
    </body>
</html>
