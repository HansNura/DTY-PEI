@extends('layouts.app')

@section('title', 'Tambah Armada')

@section('content')
    <div class="mx-auto max-w-3xl space-y-6">
        <x-page-header title="Tambah armada" description="Catat identitas kendaraan dan pengemudi utama.">
            <x-slot:actions>
                <x-button href="{{ route('logistics.fleets.index') }}" variant="ghost" icon="arrow-left">Kembali</x-button>
            </x-slot:actions>
        </x-page-header>

        <form method="POST" action="{{ route('logistics.fleets.store') }}" class="space-y-6">
            @csrf
            <x-card title="Data armada">
                @include('logistics.fleets._form')
            </x-card>

            <div class="flex flex-wrap items-center justify-end gap-2">
                <x-button href="{{ route('logistics.fleets.index') }}" variant="secondary">Batal</x-button>
                <x-button type="submit" icon="check">Simpan armada</x-button>
            </div>
        </form>
    </div>
@endsection
