@extends('layouts.app')

@section('title', 'Edit Berat Bobbin')

@section('content')
    <div class="mx-auto max-w-3xl space-y-6">
        <x-page-header title="Edit berat bobbin" :description="$roll->nomor_seri.' · '.$roll->nomor_lot">
            <x-slot:actions>
                <x-button href="{{ route('warehouse.rolls.index') }}" variant="ghost" icon="arrow-left">Kembali</x-button>
            </x-slot:actions>
        </x-page-header>

        <form method="POST" action="{{ route('warehouse.rolls.update', $roll) }}" class="space-y-6">
            @csrf
            @method('PUT')
            <x-card title="Koreksi berat">
                @include('inventory.rolls._form')
            </x-card>

            <div class="flex flex-wrap items-center justify-end gap-2">
                <x-button href="{{ route('warehouse.rolls.index') }}" variant="secondary">Batal</x-button>
                <x-button type="submit" icon="check">Simpan perubahan</x-button>
            </div>
        </form>
    </div>
@endsection
