@props(['tone' => 'neutral', 'dot' => false])

@php
    $tones = [
        'brand' => 'bg-brand-50 text-brand-700 ring-brand-200',
        'success' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'warning' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'danger' => 'bg-rose-50 text-rose-700 ring-rose-200',
        'info' => 'bg-sky-50 text-sky-700 ring-sky-200',
        'neutral' => 'bg-slate-100 text-slate-600 ring-slate-200',
    ];

    $dots = [
        'brand' => 'bg-brand-500',
        'success' => 'bg-emerald-500',
        'warning' => 'bg-amber-500',
        'danger' => 'bg-rose-500',
        'info' => 'bg-sky-500',
        'neutral' => 'bg-slate-400',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset '.($tones[$tone] ?? $tones['neutral'])]) }}>
    @if ($dot)
        <span class="size-1.5 rounded-full {{ $dots[$tone] ?? $dots['neutral'] }}"></span>
    @endif
    {{ $slot }}
</span>
