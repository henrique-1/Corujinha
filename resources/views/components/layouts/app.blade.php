<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Corujinha' }}</title>

    <link rel="icon" type="image/x-icon" href="{{ asset('images/icon/Corujinha.ico') }}">
    <link rel="shortcut icon" href="{{ asset('images/icon/Corujinha.ico') }}">

    @fonts

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-magnolia-100 text-cloud-burst-950 font-sans antialiased min-h-screen flex flex-col">
    {{ $slot }}
</body>
</html>
