@props(['padding' => 'p-5 sm:p-6', 'title' => null, 'description' => null])

<div {{ $attributes->merge(['class' => 'min-w-0 rounded-2xl border border-slate-200/80 bg-white shadow-card']) }}>
    @if ($title || $description || isset($actions))
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4 sm:px-6">
            <div class="min-w-0">
                @if ($title)
                    <h2 class="text-sm font-semibold text-slate-900">{{ $title }}</h2>
                @endif
                @if ($description)
                    <p class="mt-0.5 text-xs text-slate-500">{{ $description }}</p>
                @endif
            </div>
            @isset($actions)
                <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
            @endisset
        </div>
    @endif

    @isset($header)
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4 sm:px-6">
            {{ $header }}
        </div>
    @endisset

    <div class="{{ $padding }}">
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="border-t border-slate-100 px-5 py-4 sm:px-6">
            {{ $footer }}
        </div>
    @endisset
</div>
