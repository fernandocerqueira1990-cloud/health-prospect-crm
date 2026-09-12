<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-[#071b35]">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Entrar — CRM X</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="login-page min-h-full antialiased">
    <main class="login-shell">
        <header class="login-header">
            <div class="login-brand" aria-label="Techsallus">
                <img src="{{ asset('images/techsallus-symbol-transparent.png') }}" alt="" class="login-brand-symbol">
                <span>techsallus</span>
            </div>
        </header>

        <div class="connected-flow" aria-label="Rede de conexões comerciais">
            <svg viewBox="0 0 1280 720" role="img" aria-labelledby="flow-title flow-description">
                <title id="flow-title">Fluxo conectado</title>
                <desc id="flow-description">Rede de conexões comerciais conectada ao painel de login.</desc>

                <defs>
                    <linearGradient id="flow-line" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stop-color="#31c8f2" stop-opacity=".48"></stop>
                        <stop offset="50%" stop-color="#82eeff" stop-opacity="1"></stop>
                        <stop offset="100%" stop-color="#31c8f2" stop-opacity=".48"></stop>
                    </linearGradient>


                    <filter id="flow-glow" x="-50%" y="-50%" width="200%" height="200%">
                        <feGaussianBlur stdDeviation="5" result="blur"></feGaussianBlur>
                        <feMerge>
                            <feMergeNode in="blur"></feMergeNode>
                            <feMergeNode in="SourceGraphic"></feMergeNode>
                        </feMerge>
                    </filter>

                    <!-- linhas -->
                    <path id="flow-leads" d="M390 245 C430 245, 445 250, 470 270"></path>
                    <path id="flow-opportunities" d="M890 245 C850 245, 835 250, 810 270"></path>
                    <path id="flow-campaigns" d="M390 495 C430 495, 445 490, 470 468"></path>
                    <path id="flow-indicators" d="M890 495 C850 495, 835 490, 810 468"></path>
                </defs>

                <!-- grid -->
                <g class="connected-flow-grid" aria-hidden="true">
                    <path d="M0 115 H1280"></path>
                    <path d="M0 280 H1280"></path>
                    <path d="M0 445 H1280"></path>
                    <path d="M0 610 H1280"></path>

                    <path d="M150 0 V720"></path>
                    <path d="M340 0 V720"></path>
                    <path d="M640 0 V720"></path>
                    <path d="M940 0 V720"></path>
                    <path d="M1130 0 V720"></path>
                </g>

                <!-- box central de referência -->
                <g class="login-panel-frame" aria-hidden="true">
                    <rect x="470" y="210" width="340" height="260" rx="22"></rect>
                </g>

                <!-- linhas -->
                <g class="connected-flow-lines" aria-hidden="true">
                    <use href="#flow-leads"></use>
                    <use href="#flow-opportunities"></use>
                    <use href="#flow-campaigns"></use>
                    <use href="#flow-indicators"></use>
                </g>

                <!-- partículas -->
                <g class="connected-flow-particles" aria-hidden="true" filter="url(#flow-glow)">
                    <circle r="6">
                        <animateMotion dur="7s" repeatCount="indefinite">
                            <mpath href="#flow-leads"></mpath>
                        </animateMotion>
                    </circle>
                    <circle r="6">
                        <animateMotion dur="7s" begin="1.5s" repeatCount="indefinite">
                            <mpath href="#flow-opportunities"></mpath>
                        </animateMotion>
                    </circle>
                    <circle r="6">
                        <animateMotion dur="7s" begin="3s" repeatCount="indefinite">
                            <mpath href="#flow-campaigns"></mpath>
                        </animateMotion>
                    </circle>
                    <circle r="6">
                        <animateMotion dur="7s" begin="4.5s" repeatCount="indefinite">
                            <mpath href="#flow-indicators"></mpath>
                        </animateMotion>
                    </circle>
                </g>

                <!-- pontos do box central -->
                <g class="login-corner-nodes" aria-hidden="true">
                    <circle cx="470" cy="270" r="8"></circle>
                    <circle cx="810" cy="270" r="8"></circle>
                    <circle cx="470" cy="468" r="8"></circle>
                    <circle cx="810" cy="468" r="8"></circle>
                </g>

                <!-- Leads -->
                <g class="flow-node" transform="translate(70 185)">
                    <rect width="320" height="120" rx="24"></rect>
                    <circle cx="58" cy="60" r="24"></circle>
                    <path d="M58 46 V74 M44 60 H72"></path>
                    <text x="115" y="68">Leads</text>

                   <g class="flow-mini-icon" transform="translate(247 35)">
                      <rect x="0" y="20" width="8" height="24" rx="2"></rect>
                      <rect x="14" y="10" width="8" height="34" rx="2"></rect>
                      <rect x="28" y="0" width="8" height="44" rx="2"></rect>
                   </g>
                </g>


                <!-- Oportunidades -->
<g class="flow-node" transform="translate(890 185)">
    <rect width="320" height="120" rx="24"></rect>
    <circle cx="58" cy="60" r="24"></circle>
    <path d="M58 46 V74 M44 60 H72"></path>
    <text x="98" y="68">Oportunidades</text>


    <g class="flow-mini-icon" transform="translate(268 40)">
        <path d="M0 10 L9 2 H17 L23 8"></path>
        <path d="M39 10 L30 2 H22 L16 8"></path>
        <path d="M9 12 L17 20"></path>
        <path d="M17 20 L21 16"></path>
        <path d="M21 16 L25 20"></path>
        <path d="M25 20 L33 12"></path>
        <path d="M14 7 L21 14"></path>
        <path d="M21 7 L28 14"></path>
    </g>
</g>
                </g>

                <!-- Campanhas -->
                <g class="flow-node" transform="translate(70 435)">
                    <rect width="320" height="120" rx="24"></rect>
                    <circle cx="58" cy="60" r="24"></circle>
                    <path d="M58 46 V74 M44 60 H72"></path>
                    <text x="108" y="68">Campanhas</text>

                    <g class="flow-mini-icon" transform="translate(248 32)">
                        <path d="M2 18 H11"></path>
                        <path d="M11 15 L31 7 V35 L11 27 Z"></path>
                        <path d="M11 27 L15 42 H22 L19 29"></path>
                        <path d="M34 14 L42 10"></path>
                        <path d="M34 21 H44"></path>
                        <path d="M34 28 L42 32"></path>
                    </g>
                </g>

                <!-- Indicadores -->
                <g class="flow-node" transform="translate(890 435)">
                    <rect width="320" height="120" rx="24"></rect>
                    <circle cx="58" cy="60" r="24"></circle>
                    <path d="M58 46 V74 M44 60 H72"></path>
                    <text x="116" y="68">Indicadores</text>

                    <g class="flow-mini-icon" transform="translate(247 35)">
                        <rect x="0" y="20" width="8" height="24" rx="2"></rect>
                        <rect x="14" y="10" width="8" height="34" rx="2"></rect>
                        <rect x="28" y="0" width="8" height="44" rx="2"></rect>
                    </g>
                </g>
            </svg>
        </div>

        <section class="login-access" aria-label="Acesso à conta">
            <div class="login-form">
                <div class="mb-6">
                    <x-errors />
                </div>

                <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label class="label login-label" for="email">E-mail</label>
                        <div class="relative">
                            <svg class="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-cyan-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path d="M4 6h16v12H4z"></path>
                                <path d="m4 7 8 6 8-6"></path>
                            </svg>
                            <input class="input login-input pl-11" id="email" name="email" type="email" value="{{ old('email') }}" placeholder="seu@email.com" required autofocus autocomplete="username">
                        </div>
                    </div>

                    <div>
                        <label class="label login-label" for="password">Senha</label>
                        <div class="relative">
                            <svg class="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-cyan-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <rect x="5" y="10" width="14" height="10" rx="2"></rect>
                                <path d="M8 10V8a4 4 0 1 1 8 0v2"></path>
                            </svg>
                            <input class="input login-input pl-11" id="password" name="password" type="password" required autocomplete="current-password">
                        </div>
                    </div>

                    <label class="flex cursor-pointer items-center gap-2.5 text-sm text-slate-200">
                        <input class="h-4 w-4 rounded border-cyan-100/50 bg-white/10 text-cyan-300 focus:ring-cyan-300" name="remember" type="checkbox" value="1" @checked(old('remember'))>
                        Lembrar de mim
                    </label>

                    <button class="btn-primary w-full login-submit" type="submit">Entrar</button>
                </form>
            </div>
        </section>
    </main>
</body>
</html>
