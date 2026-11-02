@props(['name', 'size' => 'size-9'])

@php
    $initials = collect(explode(' ', trim((string) $name)))
        ->filter()
        ->map(fn (string $part): string => mb_substr($part, 0, 1))
        ->take(2)
        ->implode('');
@endphp

<span {{ $attributes->merge(['class' => "grid {$size} shrink-0 place-items-center rounded-full bg-brand-100 text-xs font-semibold uppercase text-brand-700"]) }}>
    {{ $initials !== '' ? $initials : '?' }}
</span>
