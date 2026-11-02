@extends('layouts.app')

@section('title', 'Edit Armada')

@section('content')
    <div class="mx-auto max-w-3xl space-y-6">
        <x-page-header title="Edit armada" :description="$fleet->nomor_polisi">
            <x-slot:actions>
                <x-button href="{{ route('logistics.fleets.index') }}" variant="ghost" icon="arrow-left">Kembali</x-button>
            </x-slot:actions>
        </x-page-header>

        <form method="POST" action="{{ route('logistics.fleets.update', $fleet) }}" class="space-y-6">
            @csrf
            @method('PUT')
            <x-card title="Data armada">
                @include('logistics.fleets._form')
            </x-card>

            <div class="flex flex-wrap items-center justify-end gap-2">
                <x-button href="{{ route('logistics.fleets.index') }}" variant="secondary">Batal</x-button>
                <x-button type="submit" icon="check">Simpan perubahan</x-button>
            </div>
        </form>
    </div>
@endsection
