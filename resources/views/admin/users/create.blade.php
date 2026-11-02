@extends('layouts.app')

@section('title', 'Tambah Pengguna')

@section('content')
    <div class="mx-auto max-w-3xl space-y-6">
        <x-page-header title="Tambah pengguna" description="Buat akun baru beserta peran dan hak aksesnya.">
            <x-slot:actions>
                <x-button href="{{ route('admin.users.index') }}" variant="ghost" icon="arrow-left">Kembali</x-button>
            </x-slot:actions>
        </x-page-header>

        <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-6">
            @csrf
            @include('admin.users._form')

            <div class="flex flex-wrap items-center justify-end gap-2">
                <x-button href="{{ route('admin.users.index') }}" variant="secondary">Batal</x-button>
                <x-button type="submit" icon="check">Simpan pengguna</x-button>
            </div>
        </form>
    </div>
@endsection
