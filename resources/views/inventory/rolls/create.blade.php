@extends('layouts.app')

@section('title', 'Timbang Bobbin')

@section('content')
    <div class="mx-auto max-w-3xl space-y-6">
        <x-page-header title="Timbang bobbin" description="Masukkan berat bersih bobbin. Serial, QR, merge, spesifikasi, dan grade dibuat otomatis.">
            <x-slot:actions>
                <x-button href="{{ route('warehouse.rolls.index') }}" variant="ghost" icon="arrow-left">Kembali</x-button>
            </x-slot:actions>
        </x-page-header>

        @if (! $activeBatch)
            <x-card>
                <div class="space-y-4 text-center">
                    <div class="mx-auto grid size-12 place-items-center rounded-2xl bg-amber-50 text-amber-600">
                        <x-icon name="activity" class="size-6" />
                    </div>
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">Belum ada batch produksi aktif</h2>
                        <p class="mt-1 text-sm text-slate-500">Buat dan aktifkan batch produksi terlebih dahulu agar bobbin memiliki merge dan spesifikasi yang jelas.</p>
                    </div>
                    <x-button href="{{ route('warehouse.batches.create') }}" icon="plus">Tambah batch produksi</x-button>
                </div>
            </x-card>
        @else
            <form method="POST" action="{{ route('warehouse.rolls.store') }}" class="space-y-6">
                @csrf
                <x-card title="Data penimbangan">
                    @include('inventory.rolls._form')
                </x-card>

                <div class="flex flex-wrap items-center justify-end gap-2">
                    <x-button href="{{ route('warehouse.rolls.index') }}" variant="secondary">Batal</x-button>
                    <x-button type="submit" icon="qr-code">Simpan &amp; cetak stiker</x-button>
                </div>
            </form>
        @endif
    </div>
@endsection
