@extends('layouts.app')

@section('title', 'Edit Kardus DTY')

@section('content')
    <div class="mx-auto max-w-4xl space-y-6">
        <x-page-header title="Edit kardus DTY" :description="$carton->nomor_hu.' · '.$carton->nomor_merge">
            <x-slot:actions>
                <x-button href="{{ route('warehouse.cartons.label', $carton) }}" variant="secondary" icon="printer">Lihat label</x-button>
                <x-button href="{{ route('warehouse.cartons.index') }}" variant="ghost" icon="arrow-left">Kembali</x-button>
            </x-slot:actions>
        </x-page-header>

        <form method="POST" action="{{ route('warehouse.cartons.update', $carton) }}" class="space-y-6">
            @csrf
            @method('PUT')
            @include('inventory.cartons._form', ['carton' => $carton])

            <div class="flex flex-wrap items-center justify-end gap-2">
                <x-button href="{{ route('warehouse.cartons.index') }}" variant="secondary">Batal</x-button>
                <x-button type="submit" icon="check">Simpan perubahan</x-button>
            </div>
        </form>
    </div>
@endsection
