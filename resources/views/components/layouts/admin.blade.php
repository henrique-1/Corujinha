<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-magnolia-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Painel de Gestão · Corujinha' }}</title>

    <link rel="icon" type="image/x-icon" href="{{ asset('images/icon/Corujinha.ico') }}">
    <link rel="shortcut icon" href="{{ asset('images/icon/Corujinha.ico') }}">

    @fonts

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full flex flex-col font-sans text-cloud-burst-950 antialiased bg-magnolia-100">
    {{-- Top Navigation --}}
    <nav class="bg-royal-purple-950 text-white border-b border-royal-purple-900 sticky top-0 z-40 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-6">
                    <a href="{{ auth()->user()->isSuperAdmin() ? route('admin.professores.index') : route('professor.salas.index') }}" class="flex items-center gap-3">
                        <picture>
                            <source srcset="{{ asset('images/logo/logo-horizontal.avif') }}" type="image/avif">
                            <source srcset="{{ asset('images/logo/logo-horizontal.webp') }}" type="image/webp">
                            <img src="{{ asset('images/logo/logo-horizontal.webp') }}" alt="Corujinha" class="h-10 w-auto object-contain">
                        </picture>
                        <span class="text-xs px-2.5 py-0.5 rounded-full font-bold font-heading {{ auth()->user()->isSuperAdmin() ? 'bg-broom-500/20 text-broom-300 border border-broom-500/30' : 'bg-shocking-pink-500/20 text-shocking-pink-200 border border-shocking-pink-400/30' }}">
                            {{ auth()->user()->isSuperAdmin() ? 'Super-Usuário' : 'Painel do Professor' }}
                        </span>
                    </a>

                    <div class="hidden md:flex items-center space-x-1">
                        @if(auth()->user()->isSuperAdmin())
                            <a href="{{ route('admin.professores.index') }}" class="flex items-center gap-1.5 px-3 py-2 rounded-xl text-sm font-semibold transition {{ request()->routeIs('admin.professores.*') ? 'bg-royal-purple-800 text-white' : 'text-royal-purple-200 hover:bg-royal-purple-900 hover:text-white' }}">
                                <x-lucide-users class="w-4 h-4" />
                                <span>Professores</span>
                            </a>
                        @endif

                        <a href="{{ route('professor.salas.index') }}" class="flex items-center gap-1.5 px-3 py-2 rounded-xl text-sm font-semibold transition {{ request()->routeIs('professor.salas.*') ? 'bg-royal-purple-800 text-white' : 'text-royal-purple-200 hover:bg-royal-purple-900 hover:text-white' }}">
                            <x-lucide-door-open class="w-4 h-4" />
                            <span>Salas de Aula</span>
                        </a>
                        <a href="{{ route('professor.avaliacoes.index') }}" class="flex items-center gap-1.5 px-3 py-2 rounded-xl text-sm font-semibold transition {{ request()->routeIs('professor.avaliacoes.*') ? 'bg-royal-purple-800 text-white' : 'text-royal-purple-200 hover:bg-royal-purple-900 hover:text-white' }}">
                            <x-lucide-file-text class="w-4 h-4" />
                            <span>Avaliações</span>
                        </a>
                        <a href="{{ route('professor.perguntas.index') }}" class="flex items-center gap-1.5 px-3 py-2 rounded-xl text-sm font-semibold transition {{ request()->routeIs('professor.perguntas.*') ? 'bg-royal-purple-800 text-white' : 'text-royal-purple-200 hover:bg-royal-purple-900 hover:text-white' }}">
                            <x-lucide-help-circle class="w-4 h-4" />
                            <span>Banco de Perguntas</span>
                        </a>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <a href="{{ route('security.index') }}" class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-royal-purple-900 text-royal-purple-200 hover:text-white hover:bg-royal-purple-800 transition border border-royal-purple-800">
                        <x-lucide-shield-check class="w-4 h-4" />
                        <span>Segurança (MFA)</span>
                    </a>

                    <div class="flex items-center gap-2 pl-2 border-l border-royal-purple-800">
                        <span class="text-xs text-royal-purple-200 hidden lg:inline">{{ auth()->user()->name }}</span>
                        <form action="{{ route('logout') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="cursor-pointer flex items-center gap-1 text-xs font-bold px-3 py-1.5 rounded-xl bg-shocking-pink-900/30 text-shocking-pink-200 hover:bg-shocking-pink-800/40 border border-shocking-pink-500/30 transition">
                                <x-lucide-log-out class="w-3.5 h-3.5" />
                                <span>Sair</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    {{-- Flash Notifications --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4 w-full">
        @if(session('success'))
            <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800 flex items-center justify-between shadow-xs mb-4">
                <div class="flex items-center gap-2">
                    <x-lucide-check-circle class="w-5 h-5 text-emerald-600" />
                    <span>{{ session('success') }}</span>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="rounded-xl bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800 flex items-center justify-between shadow-xs mb-4">
                <div class="flex items-center gap-2">
                    <x-lucide-alert-triangle class="w-5 h-5 text-rose-600" />
                    <span>{{ session('error') }}</span>
                </div>
            </div>
        @endif

        @if($errors->any())
            <div class="rounded-xl bg-amber-50 border border-amber-200 p-4 text-sm text-amber-900 shadow-xs mb-4">
                <p class="font-bold mb-1 flex items-center gap-1.5">
                    <x-lucide-alert-circle class="w-4 h-4 text-amber-600" />
                    <span>Atenção aos seguintes itens:</span>
                </p>
                <ul class="list-disc pl-5 space-y-1">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    {{-- Main Page Content --}}
    <main class="grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 w-full">
        {{ $slot }}
    </main>

    <footer class="mt-auto py-5 text-center text-xs text-cloud-burst-800 border-t border-magnolia-200 bg-white/70">
        Corujinha · Plataforma Aberta e Gratuita de Aprendizagem e Avaliação &copy; {{ date('Y') }}
    </footer>

    @stack('scripts')
</body>
</html>
