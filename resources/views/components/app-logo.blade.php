@props(['compact' => false])

<a href="{{ route('home') }}" {{ $attributes->merge(['class' => 'flex items-center gap-2.5']) }}>
    <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-brand-600 text-white shadow-sm">
        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
            <path d="M8 4h8M8 20h8M9 4v16M15 4v16" />
            <path d="M6 8h3M15 8h3M6 12h3M15 12h3M6 16h3M15 16h3" />
        </svg>
    </span>
    @unless ($compact)
        <span class="leading-tight">
            <span class="block text-sm font-semibold tracking-tight text-slate-900">PMT</span>
            <span class="block text-[11px] text-slate-500">Pelacakan DTY</span>
        </span>
    @endunless
</a>
