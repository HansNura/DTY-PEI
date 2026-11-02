@extends('layouts.app')

@section('title', 'Tambah Gulungan DTY')

@section('content')
    <div class="mx-auto max-w-2xl space-y-6">
        <x-page-header title="Tambah gulungan DTY" description="Kode rol dan token QR unik dibuat otomatis saat disimpan.">
            <x-slot:actions>
                <x-button href="{{ route('warehouse.rolls.index') }}" variant="ghost" icon="arrow-left">Kembali</x-button>
            </x-slot:actions>
        </x-page-header>

        <form method="POST" action="{{ route('warehouse.rolls.store') }}" class="space-y-6">
            @csrf
            <x-card title="Spesifikasi gulungan">
                @include('inventory.rolls._form')
            </x-card>

            <div class="flex flex-wrap items-center justify-end gap-2">
                <x-button href="{{ route('warehouse.rolls.index') }}" variant="secondary">Batal</x-button>
                <x-button type="submit" icon="qr-code">Simpan &amp; buat QR</x-button>
            </div>
        </form>
    </div>
@endsection
