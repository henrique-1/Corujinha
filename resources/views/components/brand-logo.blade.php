@props([
    'variant' => 'horizontal',
    'pictureClass' => '',
    'imgClass' => 'h-10 w-auto object-contain',
])

@php
    $baseName = $variant === 'icon' ? 'logo-icon' : 'logo-horizontal';
    $avifPath = "images/logo/{$baseName}.avif";
    $webpPath = "images/logo/{$baseName}.webp";
    $avifVer = file_exists(public_path($avifPath)) ? filemtime(public_path($avifPath)) : '1';
    $webpVer = file_exists(public_path($webpPath)) ? filemtime(public_path($webpPath)) : '1';
@endphp

<picture @if($pictureClass) class="{{ $pictureClass }}" @endif>
    <source srcset="{{ asset($avifPath) }}?v={{ $avifVer }}" type="image/avif">
    <source srcset="{{ asset($webpPath) }}?v={{ $webpVer }}" type="image/webp">
    <img src="{{ asset($webpPath) }}?v={{ $webpVer }}" alt="{{ config('app.name', 'Corujinha') }}" {{ $attributes->merge(['class' => $imgClass]) }}>
</picture>
