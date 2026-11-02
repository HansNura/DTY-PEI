@props([
    'action',
    'method' => 'POST',
    'title' => 'Konfirmasi tindakan',
    'description' => null,
    'confirmLabel' => 'Ya, lanjutkan',
    'tone' => 'danger',
    'variant' => 'danger',
    'size' => 'sm',
    'icon' => null,
])

<div x-data="{ open: false }">
    <x-button :variant="$variant" :size="$size" :icon="$icon" type="button" x-on:click="open = true">
        {{ $slot }}
    </x-button>

    <div x-cloak x-show="open" class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-[2px]" x-on:click="open = false"></div>

        <div class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-xl" x-on:keydown.escape.window="open = false">
            <div class="flex items-start gap-3">
                <span class="grid size-10 shrink-0 place-items-center rounded-xl {{ $tone === 'danger' ? 'bg-rose-50 text-rose-600' : 'bg-brand-50 text-brand-700' }}">
                    <x-icon :name="$tone === 'danger' ? 'alert' : 'shield'" class="size-5" />
                </span>
                <div class="min-w-0">
                    <h2 class="text-base font-semibold text-slate-900">{{ $title }}</h2>
                    @if ($description)
                        <p class="mt-1 text-sm text-slate-500">{{ $description }}</p>
                    @endif
                </div>
            </div>

            <form method="POST" action="{{ $action }}" class="mt-6 flex items-center justify-end gap-2">
                @csrf
                @if (! in_array(strtoupper($method), ['GET', 'POST'], true))
                    @method($method)
                @endif
                <x-button type="button" variant="secondary" size="sm" x-on:click="open = false">Batal</x-button>
                <x-button type="submit" :variant="$tone === 'danger' ? 'danger' : 'primary'" size="sm">{{ $confirmLabel }}</x-button>
            </form>
        </div>
    </div>
</div>
