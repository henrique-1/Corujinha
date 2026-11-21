<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ $metaDescription ?? 'Plataforma Corujinha - Realização e acompanhamento de simulados e avaliações educacionais.' }}">
    @if(isset($robots))
        <meta name="robots" content="{{ $robots }}">
    @elseif(request()->is('sala/*') || request()->is('seguranca*') || request()->is('cadastro*') || request()->is('redefinir-senha*') || request()->is('dev*'))
        <meta name="robots" content="noindex, nofollow">
    @else
        <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
    @endif
    <title>{{ $title ?? 'Corujinha' }}</title>

    {{-- SEO Canônico & Metadados para Mecanismos de Busca (Bing / Google) --}}
    <link rel="canonical" href="{{ $canonicalUrl ?? url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="pt_BR">
    <meta property="og:site_name" content="Corujinha">
    <meta property="og:title" content="{{ $title ?? 'Corujinha · Sala de Avaliação' }}">
    <meta property="og:description" content="{{ $metaDescription ?? 'Plataforma Corujinha - Realização e acompanhamento de simulados e avaliações educacionais.' }}">
    <meta property="og:url" content="{{ $canonicalUrl ?? url()->current() }}">
    <meta property="og:image" content="{{ asset('images/logo/logo-horizontal.avif') }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title ?? 'Corujinha · Sala de Avaliação' }}">
    <meta name="twitter:description" content="{{ $metaDescription ?? 'Plataforma Corujinha - Realização e acompanhamento de simulados e avaliações educacionais.' }}">
    <meta name="twitter:image" content="{{ asset('images/logo/logo-horizontal.avif') }}">

    @if(config('services.bing.site_verification'))
        {{-- Verificação de Propriedade do Bing Webmaster Tools --}}
        <meta name="msvalidate.01" content="{{ config('services.bing.site_verification') }}">
    @endif

    @if(request()->is('sala') || request()->is('login') || request()->is('/'))
        @php
            $preloadLogoPath = file_exists(public_path('images/logo/logo-horizontal-sm.avif'))
                ? 'images/logo/logo-horizontal-sm.avif'
                : 'images/logo/logo-horizontal.avif';
            $preloadLogoVer = file_exists(public_path($preloadLogoPath)) ? filemtime(public_path($preloadLogoPath)) : '1';
        @endphp
        <link rel="preload" as="image" type="image/avif" href="{{ asset($preloadLogoPath) }}?v={{ $preloadLogoVer }}" fetchpriority="high">
    @endif

    <link rel="icon" type="image/x-icon" href="{{ asset('images/icon/Corujinha.ico') }}">
    <link rel="shortcut icon" href="{{ asset('images/icon/Corujinha.ico') }}">

    @fonts

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <x-clarity />
</head>
<body class="bg-magnolia-100 text-cloud-burst-950 font-sans antialiased min-h-screen flex flex-col">
    {{ $slot }}
    <x-cookie-banner />
</body>
</html>
