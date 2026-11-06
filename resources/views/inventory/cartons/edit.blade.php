@extends('layouts.app')

@section('title', 'Edit Kardus DTY')

@section('content')
    <div class="mx-auto max-w-3xl space-y-6">
        <x-page-header title="Edit kardus DTY" :description="$carton->nomor_hu.' · Merge '.$carton->nomor_merge">
            <x-slot:actions>
                <x-button href="{{ route('warehouse.cartons.label', $carton) }}" variant="secondary" icon="printer">Lihat label</x-button>
                <x-button href="{{ route('warehouse.cartons.index') }}" variant="ghost" icon="arrow-left">Kembali</x-button>
            </x-slot:actions>
        </x-page-header>

        <x-card title="Data hasil packing" description="Data ini dihitung dari bobbin yang dipacking dan tidak dapat diubah manual.">
            <dl class="grid gap-3 sm:grid-cols-2">
                <div class="rounded-xl bg-slate-50 px-3.5 py-3 ring-1 ring-inset ring-slate-100">
                    <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Nomor HU</dt>
                    <dd class="mt-1 font-mono text-sm font-semibold text-slate-800">{{ $carton->nomor_hu }}</dd>
                </div>
                <div class="rounded-xl bg-slate-50 px-3.5 py-3 ring-1 ring-inset ring-slate-100">
                    <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Merge / lot</dt>
                    <dd class="mt-1 font-mono text-sm font-semibold text-slate-800">{{ $carton->nomor_merge }}</dd>
                </div>
                <div class="rounded-xl bg-slate-50 px-3.5 py-3 ring-1 ring-inset ring-slate-100">
                    <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Spesifikasi</dt>
                    <dd class="mt-1 text-sm font-semibold text-slate-800">DTY {{ $carton->specification }}</dd>
                </div>
                <div class="rounded-xl bg-slate-50 px-3.5 py-3 ring-1 ring-inset ring-slate-100">
                    <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Grade</dt>
                    <dd class="mt-1 text-sm font-semibold text-slate-800">{{ $carton->grade }} · {{ $carton->bobbin_weight_grade ?? '—' }}</dd>
                </div>
                <div class="rounded-xl bg-slate-50 px-3.5 py-3 ring-1 ring-inset ring-slate-100">
                    <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Net weight</dt>
                    <dd class="mt-1 text-sm font-semibold tabular-nums text-slate-800">{{ number_format((float) $carton->net_weight_kg, 2, ',', '.') }} kg</dd>
                </div>
                <div class="rounded-xl bg-slate-50 px-3.5 py-3 ring-1 ring-inset ring-slate-100">
                    <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Isi bobbin</dt>
                    <dd class="mt-1 text-sm font-semibold tabular-nums text-slate-800">{{ $carton->rolls->count() }} / {{ $carton->unit_bobbins }}</dd>
                </div>
            </dl>
        </x-card>

        <form method="POST" action="{{ route('warehouse.cartons.update', $carton) }}" class="space-y-6">
            @csrf
            @method('PUT')
            <x-card title="Koreksi data packing" description="Hanya gross weight, pallet, dan catatan yang dapat diperbarui.">
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-field label="Gross weight (kg)" name="gross_weight_kg" required hint="Minimal lebih besar dari net weight.">
                        <x-input id="gross_weight_kg" name="gross_weight_kg" type="number" min="0.01" step="0.01" value="{{ old('gross_weight_kg', $carton->gross_weight_kg) }}" placeholder="Contoh: 35.56" required />
                    </x-field>

                    <x-field label="Nomor pallet" name="pallet_number" hint="Opsional">
                        <x-input id="pallet_number" name="pallet_number" type="text" maxlength="50" value="{{ old('pallet_number', $carton->pallet_number) }}" placeholder="Contoh: ADP063296" class="font-mono uppercase" />
                    </x-field>

                    <x-field label="Catatan" name="catatan" hint="Opsional" class="sm:col-span-2">
                        <x-textarea id="catatan" name="catatan" rows="2" maxlength="500">{{ old('catatan', $carton->catatan) }}</x-textarea>
                    </x-field>
                </div>
            </x-card>

            <div class="flex flex-wrap items-center justify-end gap-2">
                <x-button href="{{ route('warehouse.cartons.index') }}" variant="secondary">Batal</x-button>
                <x-button type="submit" icon="check">Simpan perubahan</x-button>
            </div>
        </form>
    </div>
@endsection
