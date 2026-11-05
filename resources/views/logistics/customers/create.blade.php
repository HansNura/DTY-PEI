@extends('layouts.app')

@section('title', 'Tambah Pelanggan')

@section('content')
    <div class="mx-auto max-w-3xl space-y-6">
        <x-page-header title="Tambah pelanggan" description="Catat data pabrik tujuan dan kontak penerimanya.">
            <x-slot:actions>
                <x-button href="{{ route('logistics.customers.index') }}" variant="ghost" icon="arrow-left">Kembali</x-button>
            </x-slot:actions>
        </x-page-header>

        <form method="POST" action="{{ route('logistics.customers.store') }}" class="space-y-6">
            @csrf
            @include('logistics.customers._form')

            <div class="flex flex-wrap items-center justify-end gap-2">
                <x-button href="{{ route('logistics.customers.index') }}" variant="secondary">Batal</x-button>
                <x-button type="submit" icon="check">Simpan pelanggan</x-button>
            </div>
        </form>
    </div>
@endsection
