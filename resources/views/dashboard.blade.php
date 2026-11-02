@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm text-slate-500">{{ now()->translatedFormat('l, d F Y') }}</p>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Selamat datang, {{ $user->nama }}</h1>
                <p class="mt-1 text-sm text-slate-500">{{ $user->role->description() }}</p>
            </div>
            <x-badge :tone="\App\Support\StatusTone::for($user->role)" dot>{{ $user->role->label() }}</x-badge>
        </div>

        @if ($user->hasRole(\App\Enums\UserRole::Admin))
            <x-kpi-grid>
                <x-stat-card label="Total pengguna" :value="$totalUsers" icon="users" tone="brand" />
                <x-stat-card label="Akun aktif" :value="$activeUsers" icon="check" tone="success" />
                <x-stat-card label="Akun nonaktif" :value="$totalUsers - $activeUsers" icon="ban" tone="danger" />
                <x-stat-card label="Peran terdaftar" :value="count(\App\Enums\UserRole::cases())" icon="shield" tone="info" />
            </x-kpi-grid>

            <div class="grid gap-6 xl:grid-cols-[1.4fr_1fr]">
                <x-card padding="p-0" title="Pengguna terbaru" description="Lima akun yang paling baru dibuat">
                    <x-slot:actions>
                        <x-button href="{{ route('admin.users.index') }}" variant="ghost" size="sm" icon-right="arrow-right">Kelola</x-button>
                    </x-slot:actions>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-sm">
                            <thead class="bg-slate-50/70">
                                <tr>
                                    <x-th>Nama</x-th>
                                    <x-th>Peran</x-th>
                                    <x-th>Status</x-th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($latestUsers as $latestUser)
                                    <tr>
                                        <x-td>
                                            <div class="flex items-center gap-3">
                                                <x-avatar :name="$latestUser->nama" size="size-8" />
                                                <div class="min-w-0">
                                                    <p class="truncate font-medium text-slate-900">{{ $latestUser->nama }}</p>
                                                    <p class="truncate text-xs text-slate-500">{{ $latestUser->email }}</p>
                                                </div>
                                            </div>
                                        </x-td>
                                        <x-td><x-status-badge :status="$latestUser->role" /></x-td>
                                        <x-td>
                                            <x-badge :tone="$latestUser->is_active ? 'success' : 'danger'" dot>{{ $latestUser->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge>
                                        </x-td>
                                    </tr>
                                @empty
                                    <tr><x-td colspan="3" class="py-8 text-center text-slate-500">Belum ada pengguna.</x-td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-card>

                <x-card title="Sebaran peran" description="Komposisi akun saat ini">
                    <div class="space-y-4">
                        @foreach (\App\Enums\UserRole::cases() as $role)
                            @php
                                $total = (int) ($roleCounts[$role->value] ?? 0);
                                $percent = $totalUsers > 0 ? round(($total / $totalUsers) * 100) : 0;
                            @endphp
                            <div>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-slate-600">{{ $role->label() }}</span>
                                    <span class="font-semibold tabular-nums text-slate-900">{{ $total }}</span>
                                </div>
                                <div class="mt-1.5 h-2 w-full overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-2 rounded-full bg-brand-500" style="width: {{ $percent }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-card>
            </div>
        @endif

        @if ($user->hasRole(\App\Enums\UserRole::AdminGudang))
            <x-kpi-grid>
                <x-stat-card label="Stok tersedia" :value="$rollStats['in_stock']" icon="package" tone="success" />
                <x-stat-card label="Dialokasikan" :value="$rollStats['allocated']" icon="clipboard" tone="warning" />
                <x-stat-card label="Dimuat" :value="$rollStats['loaded']" icon="truck" tone="info" />
                <x-stat-card label="Dibatalkan" :value="$rollStats['cancelled']" icon="ban" tone="danger" />
            </x-kpi-grid>

            <div class="grid gap-6 xl:grid-cols-[1.4fr_1fr]">
                <x-card padding="p-0" title="Gulungan terbaru" description="Lima gulungan DTY yang terakhir dicatat">
                    <x-slot:actions>
                        <x-button href="{{ route('warehouse.rolls.index') }}" variant="ghost" size="sm" icon-right="arrow-right">Lihat stok</x-button>
                    </x-slot:actions>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-sm">
                            <thead class="bg-slate-50/70">
                                <tr>
                                    <x-th>Kode / Lot</x-th>
                                    <x-th>Spesifikasi</x-th>
                                    <x-th>Status</x-th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($recentRolls as $roll)
                                    <tr>
                                        <x-td>
                                            <p class="font-mono text-xs font-semibold text-slate-800">{{ $roll->kode_rol }}</p>
                                            <p class="text-xs text-slate-500">Lot {{ $roll->nomor_lot }}</p>
                                        </x-td>
                                        <x-td class="tabular-nums">{{ $roll->denier }}D / {{ $roll->filamen }}F</x-td>
                                        <x-td><x-status-badge :status="$roll->status" /></x-td>
                                    </tr>
                                @empty
                                    <tr><x-td colspan="3" class="py-8 text-center text-slate-500">Belum ada gulungan.</x-td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-card>

                <x-card title="Aksi cepat">
                    <div class="space-y-3">
                        <x-button href="{{ route('warehouse.rolls.create') }}" class="w-full" icon="plus">Tambah gulungan DTY</x-button>
                        <x-button href="{{ route('warehouse.scans.index') }}" variant="secondary" class="w-full" icon="scan">Buka scanner muatan</x-button>
                    </div>
                    <div class="mt-5 rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-600">
                        <p class="font-medium text-slate-900">{{ $loadingOrders }} Surat Jalan</p>
                        <p class="text-xs text-slate-500">sedang menunggu pemuatan atau validasi.</p>
                    </div>
                </x-card>
            </div>
        @endif

        @if ($user->hasRole(\App\Enums\UserRole::Logistik))
            <x-kpi-grid>
                <x-stat-card label="DO aktif" :value="$orderStats['active']" icon="clipboard" tone="brand" />
                <x-stat-card label="Menunggu muat" :value="$orderStats['loading']" icon="package" tone="warning" />
                <x-stat-card label="Dalam perjalanan" :value="$orderStats['in_transit']" icon="truck" tone="info" />
                <x-stat-card label="Tiba di tujuan" :value="$orderStats['arrived']" icon="map-pin" tone="success" />
            </x-kpi-grid>

            <div class="grid gap-6 xl:grid-cols-[1.4fr_1fr]">
                <x-card title="Pengiriman aktif" description="Pantau progres muatan dan perjalanan">
                    <x-slot:actions>
                        <x-button href="{{ route('logistics.tracking.index') }}" variant="ghost" size="sm" icon-right="arrow-right">Live tracking</x-button>
                    </x-slot:actions>

                    <div class="space-y-4">
                        @forelse ($activeOrders as $order)
                            <a href="{{ route('logistics.orders.show', $order) }}" class="block rounded-xl border border-slate-100 p-4 transition hover:border-brand-200 hover:bg-brand-50/30">
                                <div class="flex flex-wrap items-start justify-between gap-2">
                                    <div>
                                        <p class="font-mono text-sm font-semibold text-slate-900">{{ $order->nomor_do }}</p>
                                        <p class="text-xs text-slate-500">{{ $order->customer->nama_pabrik }} &middot; {{ $order->fleet->nomor_polisi }}</p>
                                    </div>
                                    <x-status-badge :status="$order->status" />
                                </div>
                                <div class="mt-3 flex items-center gap-3">
                                    <x-progress :value="$order->loaded_count" :max="$order->total_rol" class="flex-1" />
                                    <span class="text-xs tabular-nums text-slate-500">{{ $order->loaded_count }}/{{ $order->total_rol }}</span>
                                </div>
                            </a>
                        @empty
                            <x-empty-state icon="clipboard" title="Tidak ada pengiriman aktif" description="Semua Surat Jalan sudah selesai atau belum diterbitkan." />
                        @endforelse
                    </div>
                </x-card>

                <div class="space-y-6">
                    <x-card title="Aksi cepat">
                        <div class="space-y-3">
                            <x-button href="{{ route('logistics.orders.create') }}" class="w-full" icon="plus">Terbitkan Surat Jalan</x-button>
                            <x-button href="{{ route('logistics.tracking.index') }}" variant="secondary" class="w-full" icon="route">Buka live tracking</x-button>
                        </div>
                    </x-card>

                    <x-card padding="p-0" title="DO terbaru">
                        <ul class="divide-y divide-slate-100">
                            @forelse ($recentOrders as $order)
                                <li class="flex items-center justify-between gap-3 px-5 py-3">
                                    <div class="min-w-0">
                                        <p class="truncate font-mono text-xs font-semibold text-slate-800">{{ $order->nomor_do }}</p>
                                        <p class="truncate text-xs text-slate-500">{{ $order->customer->nama_pabrik }}</p>
                                    </div>
                                    <x-status-badge :status="$order->status" />
                                </li>
                            @empty
                                <li class="px-5 py-6 text-center text-sm text-slate-500">Belum ada DO.</li>
                            @endforelse
                        </ul>
                    </x-card>
                </div>
            </div>
        @endif

        @if ($user->hasRole(\App\Enums\UserRole::Driver))
            @if ($activeTrip)
                <x-card class="overflow-hidden" padding="p-0">
                    <div class="bg-gradient-to-br from-brand-700 via-brand-600 to-teal-500 p-6 text-white">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-medium uppercase tracking-wider text-white/70">Perjalanan aktif</p>
                                <p class="mt-1 font-mono text-xl font-semibold">{{ $activeTrip->nomor_do }}</p>
                                <p class="mt-1 text-sm text-white/80">{{ $activeTrip->customer->nama_pabrik }} &middot; {{ $activeTrip->fleet->nomor_polisi }}</p>
                            </div>
                            <x-badge tone="success" dot>{{ $activeTrip->status->label() }}</x-badge>
                        </div>
                        <div class="mt-5 flex items-center gap-3">
                            <x-progress :value="$activeTrip->loaded_count" :max="$activeTrip->total_rol" tone="success" class="flex-1" />
                            <span class="text-xs tabular-nums text-white/80">{{ $activeTrip->loaded_count }}/{{ $activeTrip->total_rol }} rol</span>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center justify-between gap-3 p-5">
                        <p class="text-sm text-slate-500">Perbarui status perjalanan dan kirim posisi terbaru.</p>
                        <x-button href="{{ route('driver.deliveries.show', $activeTrip) }}" icon-right="arrow-right">Buka perjalanan</x-button>
                    </div>
                </x-card>
            @else
                <x-card>
                    <x-empty-state icon="truck" title="Belum ada perjalanan aktif" description="Perjalanan akan muncul setelah checker selesai memuat dan Anda menandai berangkat.">
                        <x-slot:actions>
                            <x-button href="{{ route('driver.deliveries.index') }}" variant="secondary">Lihat semua perjalanan</x-button>
                        </x-slot:actions>
                    </x-empty-state>
                </x-card>
            @endif

            <x-kpi-grid>
                <x-stat-card label="Perjalanan hari ini" :value="$todayCount" icon="calendar" tone="brand" />
                <x-stat-card label="Total ditugaskan" :value="$assignedOrders->count()" icon="clipboard" tone="info" />
                <x-stat-card label="Perjalanan aktif" :value="$activeTrip ? 1 : 0" icon="truck" tone="success" />
                <x-stat-card label="Status akun" value="Aktif" icon="shield" tone="success" />
            </x-kpi-grid>

            <x-card padding="p-0" title="Perjalanan terbaru">
                <x-slot:actions>
                    <x-button href="{{ route('driver.deliveries.index') }}" variant="ghost" size="sm" icon-right="arrow-right">Semua</x-button>
                </x-slot:actions>

                <ul class="divide-y divide-slate-100">
                    @forelse ($assignedOrders as $order)
                        <li>
                            <a href="{{ route('driver.deliveries.show', $order) }}" class="flex items-center justify-between gap-3 px-5 py-4 transition hover:bg-slate-50">
                                <div class="min-w-0">
                                    <p class="truncate font-mono text-sm font-semibold text-slate-900">{{ $order->nomor_do }}</p>
                                    <p class="truncate text-xs text-slate-500">{{ $order->customer->nama_pabrik }} &middot; {{ $order->tanggal->translatedFormat('d M Y') }}</p>
                                </div>
                                <x-status-badge :status="$order->status" />
                            </a>
                        </li>
                    @empty
                        <li class="px-5 py-6 text-center text-sm text-slate-500">Belum ada perjalanan yang ditugaskan.</li>
                    @endforelse
                </ul>
            </x-card>
        @endif
    </div>
@endsection
