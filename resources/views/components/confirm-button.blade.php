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
    'note' => null,
])

@php
    $isDanger = $tone === 'danger';
    $modalId = 'confirm-'.uniqid();
@endphp

<div x-data="{ open: false }" x-effect="document.body.classList.toggle('overflow-hidden', open)">
    <x-button :variant="$variant" :size="$size" :icon="$icon" type="button" x-on:click="open = true">
        {{ $slot }}
    </x-button>

    <div x-cloak x-show="open" class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="{{ $modalId }}">
        <div class="absolute inset-0 bg-slate-950/45 backdrop-blur-[3px]" x-on:click="open = false"></div>

        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="scale-95 opacity-0"
            x-transition:enter-end="scale-100 opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="scale-100 opacity-100"
            x-transition:leave-end="scale-95 opacity-0"
            class="relative w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl"
            x-on:keydown.escape.window="open = false"
        >
            <div class="flex items-start gap-3.5 px-5 pb-4 pt-5 sm:px-6">
                <span class="grid size-11 shrink-0 place-items-center rounded-2xl {{ $isDanger ? 'bg-rose-50 text-rose-600 ring-1 ring-rose-100' : 'bg-brand-50 text-brand-700 ring-1 ring-brand-100' }}">
                    <x-icon :name="$isDanger ? 'alert' : 'shield'" class="size-5" />
                </span>
                <div class="min-w-0 flex-1">
                    <h2 id="{{ $modalId }}" class="pr-8 text-base font-semibold text-slate-900">{{ $title }}</h2>
                    @if ($description)
                        <p class="mt-1 text-sm leading-relaxed text-slate-500">{{ $description }}</p>
                    @endif
                </div>
                <button type="button" x-on:click="open = false" class="-mr-1 -mt-1 rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600" aria-label="Tutup">
                    <x-icon name="x" class="size-4" />
                </button>
            </div>

            @if ($note)
                <p class="mx-5 mb-5 rounded-xl px-3.5 py-3 text-xs leading-5 ring-1 ring-inset sm:mx-6 {{ $isDanger ? 'bg-rose-50/70 text-rose-700 ring-rose-100' : 'bg-brand-50/70 text-brand-700 ring-brand-100' }}">
                    {{ $note }}
                </p>
            @endif

            <form method="POST" action="{{ $action }}" class="flex flex-col-reverse gap-2 border-t border-slate-100 bg-slate-50/60 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                @csrf
                @if (! in_array(strtoupper($method), ['GET', 'POST'], true))
                    @method($method)
                @endif
                <x-button type="button" variant="secondary" size="sm" class="justify-center" x-on:click="open = false">Batal</x-button>
                <x-button type="submit" :variant="$isDanger ? 'danger' : 'primary'" size="sm" class="justify-center">{{ $confirmLabel }}</x-button>
            </form>
        </div>
    </div>
</div>
