<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-white">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Entrar — CRM X</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-full bg-white antialiased">
    <main class="min-h-screen bg-white">
        <div class="mx-auto grid min-h-screen w-full max-w-[1500px] items-center gap-12 px-6 py-8 lg:grid-cols-[1fr_520px] lg:px-12 xl:gap-20 xl:px-16">

            {{-- Área institucional --}}
            <section class="hidden lg:block">
                <img
                    src="{{ asset('images/techsallus-logo.png') }}"
                    alt="Techsallus"
                    class="h-16 w-auto object-contain"
                >

                <p class="mt-8 text-sm font-black uppercase tracking-[0.20em] text-crm-blue">
                    CRM X
                </p>

                <h1 class="mt-5 max-w-3xl text-5xl font-black leading-[1.08] tracking-tight text-crm-navy xl:text-6xl">
                    Prospecção inteligente.
                    <span class="block text-crm-blue">
                        Relacionamentos que geram valor.
                    </span>
                </h1>

                <p class="mt-7 max-w-2xl text-lg leading-8 text-slate-600">
                    Organize leads, acompanhe oportunidades e fortaleça o relacionamento
                    com seus clientes em uma plataforma comercial centralizada.
                </p>

                <div class="mt-10 flex flex-wrap gap-x-8 gap-y-4 text-sm font-semibold text-crm-navy">
                    <span class="inline-flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full bg-crm-blue"></span>
                        Gestão de Leads
                    </span>

                    <span class="inline-flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full bg-crm-sky"></span>
                        Pipeline Comercial
                    </span>

                    <span class="inline-flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full bg-crm-blue"></span>
                        Campanhas
                    </span>

                    <span class="inline-flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full bg-crm-sky"></span>
                        Indicadores e Relatórios
                    </span>
                </div>

                <div class="mt-14 h-px max-w-2xl bg-crm-light"></div>

                <div class="mt-6 flex flex-wrap gap-x-10 gap-y-3 text-xs text-slate-500">
                    <span>✓ Gestão comercial centralizada</span>
                    <span>✓ Dados protegidos</span>
                    <span>✓ Acesso corporativo</span>
                </div>
            </section>

            {{-- Login --}}
            <section class="flex w-full items-center justify-center">
                <div class="w-full max-w-lg rounded-[2rem] border border-slate-200 bg-white p-7 shadow-[0_24px_70px_rgba(18,57,93,0.10)] sm:p-10">

                    <div class="lg:hidden">
                        <img
                            src="{{ asset('images/techsallus-logo.png') }}"
                            alt="Techsallus"
                            class="h-12 w-auto object-contain"
                        >
                    </div>

                    <p class="mt-4 text-xs font-black uppercase tracking-[0.18em] text-crm-blue lg:mt-0">
                        CRM X
                    </p>

                    <h2 class="mt-4 text-4xl font-black tracking-tight text-crm-navy">
                        Acesse sua conta
                    </h2>

                    <p class="mt-3 text-sm leading-6 text-slate-600">
                        Entre com suas credenciais corporativas.
                    </p>

                    <div class="mt-6">
                        <x-errors />
                    </div>

                    <form
                        method="POST"
                        action="{{ route('login.store') }}"
                        class="mt-6 space-y-5"
                    >
                        @csrf

                        <div>
                            <label class="label text-crm-navy" for="email">
                                E-mail
                            </label>

                            <div class="relative">
                                <svg
                                    class="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-crm-blue"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                >
                                    <path d="M4 6h16v12H4z"/>
                                    <path d="m4 7 8 6 8-6"/>
                                </svg>

                                <input
                                    class="input bg-white pl-11"
                                    id="email"
                                    name="email"
                                    type="email"
                                    value="{{ old('email') }}"
                                    placeholder="seu@email.com"
                                    required
                                    autofocus
                                    autocomplete="username"
                                >
                            </div>
                        </div>

                        <div>
                            <label class="label text-crm-navy" for="password">
                                Senha
                            </label>

                            <div class="relative">
                                <svg
                                    class="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-crm-blue"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                >
                                    <rect x="5" y="10" width="14" height="10" rx="2"/>
                                    <path d="M8 10V7a4 4 0 0 1 8 0v3"/>
                                </svg>

                                <input
                                    class="input bg-white pl-11"
                                    id="password"
                                    name="password"
                                    type="password"
                                    required
                                    autocomplete="current-password"
                                >
                            </div>
                        </div>

                        <label class="flex cursor-pointer items-center gap-2.5 text-sm text-slate-700">
                            <input
                                class="h-4 w-4 rounded border-slate-300 text-crm-blue focus:ring-crm-blue"
                                name="remember"
                                type="checkbox"
                                value="1"
                                @checked(old('remember'))
                            >
                            Lembrar de mim
                        </label>

                        <button class="btn-primary w-full" type="submit">
                            Entrar
                        </button>
                    </form>
@if (config('features.public_registration'))
                    <div class="my-7 flex items-center gap-4">
                        <div class="h-px flex-1 bg-slate-200"></div>
                        <span class="text-xs text-slate-400">ou</span>
                        <div class="h-px flex-1 bg-slate-200"></div>
                    </div>

                    <div class="text-center">
                        <p class="text-sm text-slate-600">
                            Ainda não tem uma conta?
                        </p>

                        <a
                            class="mt-2 inline-flex items-center gap-1 text-sm font-bold text-crm-blue transition hover:text-crm-blue-dark"
                            href="{{ route('register') }}"
                        >
                            Criar uma conta para testar
                            <span aria-hidden="true">→</span>
                        </a>
                    </div>
@endif
                    <p class="mt-8 text-center text-[11px] text-slate-400">
                        CRM X · Gestão comercial, prospecção e relacionamento
                    </p>
                </div>
            </section>
        </div>
    </main>
</body>
</html>
