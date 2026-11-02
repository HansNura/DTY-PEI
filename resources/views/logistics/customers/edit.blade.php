@extends('layouts.app')

@section('title', 'Edit Pelanggan')

@section('content')
    <div class="mx-auto max-w-3xl space-y-6">
        <x-page-header title="Edit pelanggan" :description="$customer->nama_pabrik">
            <x-slot:actions>
                <x-button href="{{ route('logistics.customers.index') }}" variant="ghost" icon="arrow-left">Kembali</x-button>
            </x-slot:actions>
        </x-page-header>

        <form method="POST" action="{{ route('logistics.customers.update', $customer) }}" class="space-y-6">
            @csrf
            @method('PUT')
            <x-card title="Data pelanggan">
                @include('logistics.customers._form')
            </x-card>

            <div class="flex flex-wrap items-center justify-end gap-2">
                <x-button href="{{ route('logistics.customers.index') }}" variant="secondary">Batal</x-button>
                <x-button type="submit" icon="check">Simpan perubahan</x-button>
            </div>
        </form>
    </div>
@endsection
