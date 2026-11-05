@props(['customer'])

@php
    $drawerId = 'customer-drawer-'.uniqid();
    $hasCoordinates = $customer->latitude !== null && $customer->longitude !== null;
    $region = collect([$customer->kota, $customer->provinsi])->filter()->implode(', ');
    $region = trim($region.($customer->kode_pos ? ' '.$customer->kode_pos : ''));
@endphp

<div x-data="{ open: false }" x-effect="document.body.classList.toggle('overflow-hidden', open)" class="inline-flex">
    <x-button type="button" variant="ghost" size="sm" icon="eye" x-on:click="open = true" data-customer-drawer>
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
                class="absolute inset-y-0 right-0 flex w-full max-w-md flex-col border-l border-slate-200 bg-white shadow-2xl"
                x-on:keydown.escape.window="open = false"
            >
                <header class="flex items-start justify-between gap-3 border-b border-slate-100 px-5 py-4 sm:px-6">
                    <div class="min-w-0">
                        <p class="font-mono text-[11px] font-medium text-brand-700">{{ $customer->kode_pelanggan ?? '—' }}</p>
                        <h2 id="{{ $drawerId }}-title" class="mt-1 truncate text-base font-semibold text-slate-900">{{ $customer->nama_pabrik }}</h2>
                        <div class="mt-2">
                            <x-badge :tone="$customer->is_active ? 'success' : 'danger'" dot>{{ $customer->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge>
                        </div>
                    </div>
                    <button type="button" x-on:click="open = false" class="-mr-1 -mt-1 rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600" aria-label="Tutup info pelanggan">
                        <x-icon name="x" class="size-4" />
                    </button>
                </header>

                <div class="flex-1 divide-y divide-slate-100 overflow-y-auto px-5 sm:px-6">
                    <section class="py-5">
                        <h3 class="text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-500">Alamat pengiriman</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-700">{{ $customer->alamat }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $region ?: 'Kota, provinsi, dan kode pos belum diisi.' }}</p>
                    </section>

                    <section class="py-5">
                        <h3 class="text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-500">Kontak penerima</h3>
                        <p class="mt-2 text-sm font-medium text-slate-800">
                            {{ $customer->kontak_nama }}
                            @if ($customer->kontak_jabatan)
                                <span class="font-normal text-slate-500">· {{ $customer->kontak_jabatan }}</span>
                            @endif
                        </p>
                        <div class="mt-2 space-y-1.5 text-sm">
                            <a href="tel:{{ $customer->kontak_telepon }}" class="flex items-center gap-2 text-slate-600 transition hover:text-brand-700">
                                <x-icon name="phone" class="size-4 text-slate-400" />
                                {{ $customer->kontak_telepon }}
                            </a>
                            @if ($customer->email)
                                <a href="mailto:{{ $customer->email }}" class="flex items-center gap-2 text-slate-600 transition hover:text-brand-700">
                                    <x-icon name="mail" class="size-4 text-slate-400" />
                                    {{ $customer->email }}
                                </a>
                            @endif
                        </div>
                    </section>

                    <section class="py-5">
                        <h3 class="text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-500">Informasi perusahaan</h3>
                        <dl class="mt-3 grid grid-cols-2 gap-4">
                            <div>
                                <dt class="text-xs text-slate-500">NPWP</dt>
                                <dd class="mt-0.5 text-sm text-slate-700">{{ $customer->npwp ?: '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-slate-500">Kode pelanggan</dt>
                                <dd class="mt-0.5 font-mono text-sm font-medium text-slate-700">{{ $customer->kode_pelanggan ?? '—' }}</dd>
                            </div>
                        </dl>
                    </section>

                    <section class="py-5">
                        <h3 class="text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-500">Lokasi peta</h3>
                        @if ($hasCoordinates)
                            <p class="mt-2 font-mono text-xs text-slate-500">{{ $customer->latitude }}, {{ $customer->longitude }}</p>
                            <x-button
                                href="https://maps.google.com/?q={{ $customer->latitude }},{{ $customer->longitude }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                variant="secondary"
                                size="sm"
                                icon="map-pin"
                                icon-right="external"
                                class="mt-3"
                            >
                                Buka di Google Maps
                            </x-button>
                        @else
                            <p class="mt-2 text-sm text-slate-500">Titik peta belum diatur.</p>
                        @endif
                    </section>

                    @if ($customer->catatan)
                        <section class="py-5">
                            <h3 class="text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-500">Catatan</h3>
                            <p class="mt-2 rounded-xl bg-slate-50 px-3.5 py-3 text-sm leading-6 text-slate-600 ring-1 ring-inset ring-slate-100">{{ $customer->catatan }}</p>
                        </section>
                    @endif
                </div>

                <footer class="border-t border-slate-100 px-5 py-4 sm:px-6">
                    <x-button href="{{ route('logistics.customers.edit', $customer) }}" icon="edit" class="w-full justify-center">Edit pelanggan</x-button>
                </footer>
            </aside>
        </div>
    </template>
</div>
