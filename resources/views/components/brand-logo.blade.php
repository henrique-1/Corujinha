@props([
    'variant' => 'horizontal',
    'size' => 'sm',
    'pictureClass' => '',
    'imgClass' => 'h-10 w-auto object-contain',
    'fetchpriority' => 'high',
])

@php
    $suffix = $size === 'sm' ? '-sm' : '';
    $baseName = $variant === 'icon' ? "logo-icon{$suffix}" : "logo-horizontal{$suffix}";
    $avifPath = "images/logo/{$baseName}.avif";
    $webpPath = "images/logo/{$baseName}.webp";

    if (! file_exists(public_path($avifPath))) {
        $baseName = $variant === 'icon' ? 'logo-icon' : 'logo-horizontal';
        $avifPath = "images/logo/{$baseName}.avif";
        $webpPath = "images/logo/{$baseName}.webp";
    }

    $avifVer = file_exists(public_path($avifPath)) ? filemtime(public_path($avifPath)) : '1';
    $webpVer = file_exists(public_path($webpPath)) ? filemtime(public_path($webpPath)) : '1';

    $defaultWidth = $variant === 'icon' ? 80 : 273;
    $defaultHeight = $variant === 'icon' ? 98 : 112;
@endphp

<picture @if($pictureClass) class="{{ $pictureClass }}" @endif>
    <source srcset="{{ asset($avifPath) }}?v={{ $avifVer }}" type="image/avif">
    <source srcset="{{ asset($webpPath) }}?v={{ $webpVer }}" type="image/webp">
    <img src="{{ asset($webpPath) }}?v={{ $webpVer }}"
         alt="{{ config('app.name', 'Corujinha') }}"
         width="{{ $attributes->get('width', $defaultWidth) }}"
         height="{{ $attributes->get('height', $defaultHeight) }}"
         fetchpriority="{{ $fetchpriority }}"
         {{ $attributes->except(['width', 'height', 'fetchpriority'])->merge(['class' => $imgClass]) }}>
</picture>
