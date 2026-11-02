@props(['tone' => 'success', 'title' => null, 'dismissible' => false])

@php
    $tones = [
        'success' => ['bg-emerald-50', 'border-emerald-200', 'text-emerald-800', 'check'],
        'danger' => ['bg-rose-50', 'border-rose-200', 'text-rose-800', 'alert'],
        'warning' => ['bg-amber-50', 'border-amber-200', 'text-amber-800', 'alert'],
        'info' => ['bg-sky-50', 'border-sky-200', 'text-sky-800', 'activity'],
    ];

    [$bg, $border, $text, $icon] = $tones[$tone] ?? $tones['success'];
@endphp

<div {{ $attributes->merge(['class' => "flex items-start gap-3 rounded-xl border {$border} {$bg} px-4 py-3 text-sm {$text}"]) }}
     @if ($dismissible) x-data="{ show: true }" x-show="show" @endif>
    <x-icon :name="$icon" class="mt-0.5 size-5 shrink-0" />
    <div class="min-w-0 flex-1">
        @if ($title)
            <p class="font-semibold">{{ $title }}</p>
        @endif
        <div class="{{ $title ? 'mt-0.5' : '' }}">{{ $slot }}</div>
    </div>
    @if ($dismissible)
        <button type="button" x-on:click="show = false" class="shrink-0 opacity-70 transition hover:opacity-100" aria-label="Tutup">
            <x-icon name="x" class="size-4" />
        </button>
    @endif
</div>
