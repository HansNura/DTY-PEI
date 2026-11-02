@props(['align' => 'left'])

@php
    $alignClass = match ($align) {
        'right' => 'text-right',
        'center' => 'text-center',
        default => 'text-left',
    };
@endphp

<th {{ $attributes->merge(['class' => "whitespace-nowrap px-5 py-3 {$alignClass} text-[11px] font-semibold uppercase tracking-wider text-slate-500"]) }}>
    {{ $slot }}
</th>
