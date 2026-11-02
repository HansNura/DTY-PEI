@props(['icon' => 'boxes', 'title', 'description' => null])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-white px-6 py-10 text-center']) }}>
    <span class="grid size-12 place-items-center rounded-2xl bg-slate-100 text-slate-500">
        <x-icon :name="$icon" class="size-6" />
    </span>
    <p class="mt-4 text-sm font-semibold text-slate-900">{{ $title }}</p>
    @if ($description)
        <p class="mt-1 max-w-md text-sm text-slate-500">{{ $description }}</p>
    @endif
    @isset($actions)
        <div class="mt-5 flex flex-wrap items-center justify-center gap-2">{{ $actions }}</div>
    @endisset
</div>
