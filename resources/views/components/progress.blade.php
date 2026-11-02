@props(['value' => 0, 'max' => 100, 'tone' => 'brand'])

@php
    $percent = $max > 0 ? max(0, min(100, ((float) $value / (float) $max) * 100)) : 0;

    $tones = [
        'brand' => 'bg-brand-600',
        'success' => 'bg-emerald-500',
        'warning' => 'bg-amber-500',
        'danger' => 'bg-rose-500',
        'info' => 'bg-sky-500',
    ];
@endphp

<div {{ $attributes }}>
    <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100">
        <div class="h-2 rounded-full transition-all duration-500 {{ $tones[$tone] ?? $tones['brand'] }}" style="width: {{ $percent }}%"></div>
    </div>
</div>
