@props([
    'user' => null,
    'name' => null,
    'email' => null,
    'size' => 'md',
    'class' => '',
])

@php
    $displayName = $name ?? $user?->name ?? $email ?? $user?->email ?? 'Professor';
    $avatarUrl = $user?->avatarUrl();

    $dimensionClasses = match($size) {
        'xs' => 'w-6 h-6 text-[10px]',
        'sm' => 'w-8 h-8 text-xs',
        'md' => 'w-10 h-10 text-sm',
        'lg' => 'w-12 h-12 text-base',
        'xl' => 'w-16 h-16 text-xl',
        default => 'w-10 h-10 text-sm',
    };

    if ($user) {
        $initials = $user->initials();
    } else {
        $parts = array_values(array_filter(explode(' ', trim((string) $displayName))));
        $initials = count($parts) > 1
            ? mb_strtoupper(mb_substr($parts[0], 0, 1) . mb_substr(end($parts), 0, 1))
            : mb_strtoupper(mb_substr($displayName, 0, 2));
    }
@endphp

<div class="relative shrink-0 inline-flex items-center justify-center {{ $dimensionClasses }} {{ $class }}">
    @if($avatarUrl)
        <img src="{{ $avatarUrl }}"
             alt="{{ $displayName }}"
             class="w-full h-full object-cover rounded-full border-2 border-royal-purple-200/80 shadow-2xs">
    @else
        <div class="w-full h-full rounded-full bg-linear-to-br from-royal-purple-900 to-royal-purple-700 text-white font-extrabold font-heading flex items-center justify-center border-2 border-royal-purple-300/40 shadow-2xs select-none">
            <span>{{ $initials }}</span>
        </div>
    @endif
</div>
