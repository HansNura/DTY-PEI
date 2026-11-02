@props([
    'label',
    'value',
    'icon' => 'activity',
    'tone' => 'brand',
    'hint' => null,
])

@php
    $tones = [
        'brand' => 'bg-brand-50 text-brand-700',
        'info' => 'bg-sky-50 text-sky-700',
        'success' => 'bg-emerald-50 text-emerald-700',
        'warning' => 'bg-amber-50 text-amber-700',
        'danger' => 'bg-rose-50 text-rose-700',
        'neutral' => 'bg-slate-100 text-slate-600',
    ];
@endphp

<x-card padding="p-5">
    <div class="flex items-start justify-between gap-4">
        <div class="min-w-0">
            <p class="text-sm font-medium text-slate-500">{{ $label }}</p>
            <p class="mt-2 text-2xl font-semibold tracking-tight tabular-nums text-slate-900">{{ $value }}</p>
            @if ($hint)
                <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
            @endif
        </div>
        <span class="grid size-10 shrink-0 place-items-center rounded-xl {{ $tones[$tone] ?? $tones['brand'] }}">
            <x-icon :name="$icon" class="size-5" />
        </span>
    </div>
</x-card>
