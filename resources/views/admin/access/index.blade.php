@extends('layouts.app')

@section('title', 'Hak Akses')

@section('content')
    <div class="space-y-6">
        <x-page-header title="Ringkasan hak akses" description="Semua hak yang diperlukan tiap peran, dibaca langsung dari aturan akses pada route aplikasi.">
            <x-slot:actions>
                <x-button href="{{ route('admin.users.index') }}" variant="secondary" icon="users">Kelola pengguna</x-button>
            </x-slot:actions>
        </x-page-header>

        <x-kpi-grid>
            <x-stat-card label="Peran" :value="count($roles)" icon="users" tone="brand" />
            <x-stat-card label="Modul" :value="$totalModules" icon="shield" tone="info" />
            <x-stat-card label="Akun aktif" :value="$totalActiveUsers" icon="check" tone="success" />
            <x-stat-card label="Khusus admin" :value="$adminOnlyModules" icon="lock" tone="warning" />
        </x-kpi-grid>

        <x-alert tone="info" title="Sumber aturan akses">
            Matriks ini mengikuti middleware peran pada route. Menyembunyikan menu di tampilan tidak cukup; akses tetap ditolak di server bila peran tidak sesuai.
        </x-alert>

        <x-card padding="p-0" title="Matriks akses per peran" description="Centang berarti peran boleh mengakses modul tersebut.">
            <x-slot:actions>
                <div class="flex items-center gap-3 text-[11px] text-slate-500">
                    <span class="inline-flex items-center gap-1"><x-icon name="check" class="size-3.5 text-emerald-600" /> Boleh</span>
                    <span class="inline-flex items-center gap-1"><span class="text-slate-300">—</span> Tidak</span>
                </div>
            </x-slot:actions>

            <div class="hidden overflow-x-auto md:block">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50/70">
                        <tr>
                            <x-th class="min-w-64">Modul</x-th>
                            @foreach ($roles as $role)
                                <x-th align="center" class="whitespace-nowrap">
                                    <x-status-badge :status="$role" />
                                </x-th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($moduleGroups as $group => $groupModules)
                            <tr class="bg-slate-50/50">
                                <x-td colspan="{{ count($roles) + 1 }}" class="py-2 text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                                    {{ $group }}
                                </x-td>
                            </tr>
                            @foreach ($groupModules as $module)
                                <tr class="align-top hover:bg-slate-50/60">
                                    <x-td>
                                        <div class="flex items-start gap-3">
                                            <span class="mt-0.5 grid size-8 shrink-0 place-items-center rounded-lg bg-slate-100 text-slate-500">
                                                <x-icon :name="$module['icon']" class="size-4" />
                                            </span>
                                            <div class="min-w-0">
                                                @if ($module['route'])
                                                    <a href="{{ route($module['route']) }}" class="font-medium text-slate-900 hover:text-brand-700">{{ $module['label'] }}</a>
                                                @else
                                                    <p class="font-medium text-slate-900">{{ $module['label'] }}</p>
                                                @endif
                                                <p class="mt-0.5 text-xs leading-5 text-slate-500">{{ $module['description'] }}</p>
                                            </div>
                                        </div>
                                    </x-td>
                                    @foreach ($roles as $role)
                                        @php $allowed = in_array($role, $module['roles'], true); @endphp
                                        <x-td align="center">
                                            @if ($allowed)
                                                <span class="inline-grid size-6 place-items-center rounded-full bg-emerald-50 text-emerald-700" title="{{ $role->label() }} boleh mengakses {{ $module['label'] }}">
                                                    <x-icon name="check" class="size-3.5" />
                                                    <span class="sr-only">{{ $role->label() }} boleh mengakses {{ $module['label'] }}</span>
                                                </span>
                                            @else
                                                <span class="text-slate-300" title="{{ $role->label() }} tidak memiliki akses ke {{ $module['label'] }}">
                                                    —
                                                    <span class="sr-only">{{ $role->label() }} tidak memiliki akses ke {{ $module['label'] }}</span>
                                                </span>
                                            @endif
                                        </x-td>
                                    @endforeach
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="space-y-5 p-4 md:hidden">
                @foreach ($moduleGroups as $group => $groupModules)
                    <div>
                        <p class="mb-2 text-[10px] font-semibold uppercase tracking-wider text-slate-400">{{ $group }}</p>
                        <div class="space-y-3">
                            @foreach ($groupModules as $module)
                                <div class="rounded-xl border border-slate-200 p-3.5">
                                    <div class="flex items-start gap-2.5">
                                        <span class="mt-0.5 grid size-8 shrink-0 place-items-center rounded-lg bg-slate-100 text-slate-500">
                                            <x-icon :name="$module['icon']" class="size-4" />
                                        </span>
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium text-slate-900">{{ $module['label'] }}</p>
                                            <p class="mt-0.5 text-xs leading-5 text-slate-500">{{ $module['description'] }}</p>
                                        </div>
                                    </div>
                                    <div class="mt-3 flex flex-wrap gap-1.5">
                                        @foreach ($roles as $role)
                                            @php $allowed = in_array($role, $module['roles'], true); @endphp
                                            <x-badge :tone="$allowed ? 'success' : 'neutral'">
                                                {{ $role->label() }}
                                            </x-badge>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </x-card>

        <div class="grid gap-4 lg:grid-cols-2">
            @foreach ($roleSummaries as $summary)
                <x-card>
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <x-status-badge :status="$summary['role']" />
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $summary['role']->description() }}</p>
                        </div>
                        <x-badge tone="neutral">{{ $summary['active'] }}/{{ $summary['total'] }} akun aktif</x-badge>
                    </div>

                    <div class="mt-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Akses modul ({{ count($summary['modules']) }})</p>
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            @foreach ($summary['modules'] as $module)
                                <x-badge tone="brand">{{ $module['label'] }}</x-badge>
                            @endforeach
                        </div>
                    </div>

                    <p class="mt-3 border-t border-slate-100 pt-3 text-xs text-slate-500">
                        {{ $summary['total'] }} akun terdaftar · {{ $summary['total'] - $summary['active'] }} nonaktif
                    </p>
                </x-card>
            @endforeach
        </div>

        <x-card title="Aturan penting" description="Batas tanggung jawab yang harus dijaga saat mengelola akun.">
            <ul class="space-y-3 text-sm text-slate-600">
                <li class="flex gap-2"><x-icon name="shield" class="mt-0.5 size-4 shrink-0 text-brand-600" /><span><strong class="font-medium text-slate-800">Administrator</strong> hanya mengelola akun dan hak akses; tidak membuka area gudang, logistik, atau driver.</span></li>
                <li class="flex gap-2"><x-icon name="boxes" class="mt-0.5 size-4 shrink-0 text-brand-600" /><span><strong class="font-medium text-slate-800">Admin Gudang</strong> menangani produksi, kardus, stok bobbin, packing, dan scanner muatan.</span></li>
                <li class="flex gap-2"><x-icon name="truck" class="mt-0.5 size-4 shrink-0 text-brand-600" /><span><strong class="font-medium text-slate-800">Tim Logistik</strong> mengelola pelanggan, armada, surat jalan, tracking, dan rekap pengiriman.</span></li>
                <li class="flex gap-2"><x-icon name="map-pin" class="mt-0.5 size-4 shrink-0 text-brand-600" /><span><strong class="font-medium text-slate-800">Driver</strong> hanya melihat perjalanan miliknya sendiri, memperbarui status, dan mengisi POD.</span></li>
                <li class="flex gap-2"><x-icon name="lock" class="mt-0.5 size-4 shrink-0 text-brand-600" /><span>Akun nonaktif langsung diputus dari sesi dan tidak dapat masuk sampai diaktifkan kembali. Administrator tidak dapat menonaktifkan atau mengubah peran akunnya sendiri.</span></li>
            </ul>
        </x-card>
    </div>
@endsection
