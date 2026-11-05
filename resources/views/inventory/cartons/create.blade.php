@extends('layouts.app')

@section('title', 'Tambah Kardus DTY')

@section('content')
    <div class="mx-auto max-w-4xl space-y-6">
        <x-page-header title="Tambah kardus DTY" description="Satu kardus akan otomatis membuat bobbin dan QR unik sesuai jumlah unit bobbin.">
            <x-slot:actions>
                <x-button href="{{ route('warehouse.cartons.index') }}" variant="ghost" icon="arrow-left">Kembali</x-button>
            </x-slot:actions>
        </x-page-header>

        <form method="POST" action="{{ route('warehouse.cartons.store') }}" class="space-y-6">
            @csrf
            @include('inventory.cartons._form')

            <div class="flex flex-wrap items-center justify-end gap-2">
                <x-button href="{{ route('warehouse.cartons.index') }}" variant="secondary">Batal</x-button>
                <x-button type="submit" icon="boxes">Simpan &amp; buat bobbin</x-button>
            </div>
        </form>
    </div>
@endsection
