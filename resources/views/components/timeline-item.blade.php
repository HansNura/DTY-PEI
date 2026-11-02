@props(['title', 'meta' => null, 'tone' => 'brand'])

@php
    $dots = [
        'brand' => 'bg-brand-500',
        'success' => 'bg-emerald-500',
        'warning' => 'bg-amber-500',
        'danger' => 'bg-rose-500',
        'info' => 'bg-sky-500',
        'neutral' => 'bg-slate-400',
    ];
@endphp

<li {{ $attributes->merge(['class' => 'relative']) }}>
    <span class="absolute -left-[1.4rem] top-1.5 size-3 rounded-full ring-4 ring-white {{ $dots[$tone] ?? $dots['brand'] }}"></span>
    <div class="flex flex-wrap items-center gap-2">
        <p class="text-sm font-medium text-slate-900">{{ $title }}</p>
        @if ($meta)
            <span class="text-xs text-slate-500">{{ $meta }}</span>
        @endif
    </div>
    @if (trim((string) $slot) !== '')
        <div class="mt-1 text-sm text-slate-500">{{ $slot }}</div>
    @endif
</li>
