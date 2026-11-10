<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'PREPARA AVALIA+ · 7º ano' }}</title>

    @fonts

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#f2effb] text-[#24304a] font-sans antialiased min-h-screen flex flex-col">
    {{ $slot }}
</body>
</html>
