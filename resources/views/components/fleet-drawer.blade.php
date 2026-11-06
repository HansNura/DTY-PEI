@props(['fleet'])

@php
    $drawerId = 'fleet-drawer-'.uniqid();
    $capacity = number_format((float) $fleet->kapasitas_kg, 0, ',', '.');
    $deliveryOrders = (int) ($fleet->delivery_orders_count ?? 0);
@endphp

<div x-data="{ open: false }" x-effect="document.body.classList.toggle('overflow-hidden', open)" class="inline-flex">
    <x-button type="button" variant="ghost" size="sm" icon="eye" x-on:click="open = true" data-fleet-drawer>
        Info
    </x-button>

    <template x-teleport="body">
        <div x-cloak x-show="open" class="fixed inset-0 z-50" role="dialog" aria-modal="true" aria-labelledby="{{ $drawerId }}-title">
            <div class="absolute inset-0 bg-slate-950/40 backdrop-blur-[2px]" x-on:click="open = false"></div>

            <aside
                x-show="open"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="translate-x-full"
                class="absolute inset-y-0 right-0 flex w-full max-w-lg flex-col border-l border-slate-200 bg-white shadow-2xl"
                x-on:keydown.escape.window="open = false"
            >
                <header class="border-b border-slate-100 px-5 py-5 sm:px-6">
                    <div class="flex items-start gap-3.5">
                        <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-brand-50 text-brand-700 ring-1 ring-brand-100">
                            <x-icon name="truck" class="size-5" />
                        </span>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-mono text-[11px] font-medium text-brand-700">{{ $fleet->kode_armada ?? '—' }}</span>
                                <x-badge :tone="$fleet->is_active ? 'success' : 'danger'" dot>{{ $fleet->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge>
                            </div>
                            <h2 id="{{ $drawerId }}-title" class="mt-1.5 truncate text-lg font-semibold tracking-tight text-slate-900">{{ $fleet->nomor_polisi }}</h2>
                            <p class="mt-1 text-xs text-slate-500">{{ $fleet->jenis_kendaraan }}</p>
                        </div>

                        <button type="button" x-on:click="open = false" class="-mr-1 -mt-1 rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600" aria-label="Tutup info armada">
                            <x-icon name="x" class="size-4" />
                        </button>
                    </div>
                </header>

                <div class="flex-1 divide-y divide-slate-100 overflow-y-auto px-5 sm:px-6">
                    <section class="py-5">
                        <h3 class="text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-500">Ringkasan</h3>
                        <dl class="mt-3 grid grid-cols-2 gap-3">
                            <div class="rounded-xl bg-slate-50 px-3.5 py-3 ring-1 ring-inset ring-slate-100">
                                <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Kapasitas</dt>
                                <dd class="mt-1 text-sm font-semibold tabular-nums text-slate-800">{{ $capacity }} kg</dd>
                            </div>
                            <div class="rounded-xl bg-slate-50 px-3.5 py-3 ring-1 ring-inset ring-slate-100">
                                <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Surat jalan</dt>
                                <dd class="mt-1 text-sm font-semibold tabular-nums text-slate-800">{{ $deliveryOrders }} dokumen</dd>
                            </div>
                        </dl>
                    </section>

                    <section class="py-5">
                        <h3 class="text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-500">Pengemudi utama</h3>
                        @if ($fleet->driver)
                            <p class="mt-2 text-sm font-medium text-slate-800">{{ $fleet->driver->nama }}</p>
                            <a href="mailto:{{ $fleet->driver->email }}" class="mt-1 inline-flex items-center gap-2 text-xs text-slate-500 transition hover:text-brand-700">
                                <x-icon name="mail" class="size-3.5" />
                                {{ $fleet->driver->email }}
                            </a>
                        @else
                            <p class="mt-2 text-sm text-slate-500">Belum ditentukan.</p>
                            <p class="mt-1 text-xs text-slate-400">Pilih pengemudi lewat tombol edit armada.</p>
                        @endif
                    </section>

                    <section class="py-5">
                        <h3 class="text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-500">Keterangan</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Armada ini digunakan sebagai kendaraan pengiriman DTY. Kapasitas menjadi acuan saat membuat surat jalan.</p>
                    </section>
                </div>

                <footer class="flex gap-2 border-t border-slate-100 px-5 py-4 sm:px-6">
                    <x-button type="button" variant="secondary" size="sm" class="flex-1 justify-center" x-on:click="open = false">Tutup</x-button>
                    <x-button href="{{ route('logistics.fleets.edit', $fleet) }}" size="sm" icon="edit" class="flex-1 justify-center">Edit armada</x-button>
                </footer>
            </aside>
        </div>
    </template>
</div>
