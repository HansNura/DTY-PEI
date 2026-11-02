@extends('layouts.app')

@section('title', 'Surat Jalan')

@section('content')
    <div class="space-y-6">
        <x-page-header title="Surat Jalan" description="Kelola dan pantau seluruh pengiriman rol benang DTY.">
            <x-slot:actions>
                <x-button href="{{ route('logistics.orders.create') }}" icon="plus">Terbitkan Surat Jalan</x-button>
            </x-slot:actions>
        </x-page-header>

        <x-card padding="p-4">
            <form method="GET" action="{{ route('logistics.orders.index') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[1.6fr_1fr_auto]">
                <x-field name="q">
                    <x-input id="q" name="q" type="search" value="{{ $search }}" placeholder="Cari nomor DO, pelanggan, atau armada" aria-label="Cari surat jalan" />
                </x-field>

                <x-field name="status">
                    <x-select id="status" name="status" aria-label="Filter status">
                        <option value="">Semua status</option>
                        @foreach ($statuses as $s)
                            <option value="{{ $s->value }}" @selected($status === $s->value)>{{ $s->label() }}</option>
                        @endforeach
                    </x-select>
                </x-field>

                <div class="flex items-end gap-2">
                    <x-button type="submit" variant="secondary" icon="filter">Filter</x-button>
                    <x-button href="{{ route('logistics.orders.index') }}" variant="ghost">Reset</x-button>
                </div>
            </form>
        </x-card>

        <x-table class="hidden md:block" padding="p-0">
            <x-slot:footer><x-pagination :paginator="$orders" class="!mt-0" /></x-slot:footer>

            <thead class="bg-slate-50/70">
                <tr>
                    <x-th>Nomor DO / Tanggal</x-th>
                    <x-th>Pelanggan</x-th>
                    <x-th>Armada / Driver</x-th>
                    <x-th>Muatan</x-th>
                    <x-th>Status</x-th>
                    <x-th align="right">Aksi</x-th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($orders as $order)
                    <tr class="hover:bg-slate-50/60">
                        <x-td>
                            <p class="font-mono text-xs font-semibold text-slate-900">{{ $order->nomor_do }}</p>
                            <p class="text-xs text-slate-500">{{ $order->tanggal->translatedFormat('d M Y') }}</p>
                        </x-td>
                        <x-td>
                            <p class="font-medium text-slate-800">{{ $order->customer->nama_pabrik }}</p>
                            <p class="max-w-[180px] truncate text-xs text-slate-500">{{ $order->customer->alamat }}</p>
                        </x-td>
                        <x-td>
                            <p class="font-medium text-slate-800">{{ $order->fleet->nomor_polisi }}</p>
                            <p class="text-xs text-slate-500">{{ $order->driver->nama }}</p>
                        </x-td>
                        <x-td>
                            <div class="flex items-center gap-2">
                                <x-progress :value="$order->loaded_count" :max="$order->items->count()" class="w-20" />
                                <span class="text-xs tabular-nums text-slate-500">{{ $order->loaded_count }}/{{ $order->items->count() }}</span>
                            </div>
                        </x-td>
                        <x-td><x-status-badge :status="$order->status" /></x-td>
                        <x-td align="right">
                            <x-button href="{{ route('logistics.orders.show', $order) }}" variant="ghost" size="sm" icon-right="chevron-right">Detail</x-button>
                        </x-td>
                    </tr>
                @empty
                    <tr><x-td colspan="6" class="py-8 text-center text-slate-500">Belum ada surat jalan yang cocok dengan filter.</x-td></tr>
                @endforelse
            </tbody>
        </x-table>

        <div class="space-y-3 md:hidden">
            @forelse ($orders as $order)
                <x-card padding="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-mono text-xs font-semibold text-slate-900">{{ $order->nomor_do }}</p>
                            <p class="truncate text-sm text-slate-700">{{ $order->customer->nama_pabrik }}</p>
                            <p class="text-xs text-slate-500">{{ $order->fleet->nomor_polisi }} · {{ $order->driver->nama }}</p>
                        </div>
                        <x-status-badge :status="$order->status" />
                    </div>
                    <div class="mt-3 flex items-center gap-3">
                        <x-progress :value="$order->loaded_count" :max="$order->items->count()" class="flex-1" />
                        <span class="text-xs tabular-nums text-slate-500">{{ $order->loaded_count }}/{{ $order->items->count() }} rol</span>
                    </div>
                    <div class="mt-4 flex items-center justify-between gap-3 border-t border-slate-100 pt-3">
                        <span class="text-xs text-slate-500">{{ $order->tanggal->translatedFormat('d M Y') }}</span>
                        <x-button href="{{ route('logistics.orders.show', $order) }}" variant="secondary" size="sm">Detail</x-button>
                    </div>
                </x-card>
            @empty
                <x-empty-state icon="clipboard" title="Belum ada surat jalan" description="Terbitkan surat jalan pertama untuk mulai mengirim rol DTY." />
            @endforelse
            <x-pagination :paginator="$orders" />
        </div>
    </div>
@endsection
