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
    @endif
    <title>{{ $title ?? 'Corujinha' }}</title>

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
</head>
<body class="bg-magnolia-100 text-cloud-burst-950 font-sans antialiased min-h-screen flex flex-col">
    {{ $slot }}
</body>
</html>
