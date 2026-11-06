@extends('layouts.app')

@section('title', 'Tambah Batch Produksi')

@section('content')
    <div class="mx-auto max-w-3xl space-y-6">
        <x-page-header title="Tambah batch produksi" description="Tentukan merge dan spesifikasi benang yang sedang diproduksi. Bobbin akan mengikuti batch aktif.">
            <x-slot:actions>
                <x-button href="{{ route('warehouse.batches.index') }}" variant="ghost" icon="arrow-left">Kembali</x-button>
            </x-slot:actions>
        </x-page-header>

        <form method="POST" action="{{ route('warehouse.batches.store') }}" class="space-y-6">
            @csrf
            <x-card title="Data batch">
                @include('inventory.batches._form')
            </x-card>

            <div class="flex flex-wrap items-center justify-end gap-2">
                <x-button href="{{ route('warehouse.batches.index') }}" variant="secondary">Batal</x-button>
                <x-button type="submit" icon="check">Simpan batch</x-button>
            </div>
        </form>
    </div>
@endsection
