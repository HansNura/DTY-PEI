@extends('layouts.app')

@section('title', 'Edit Pengguna')

@section('content')
    <div class="mx-auto max-w-3xl space-y-6">
        <x-page-header title="Edit pengguna" :description="$user->email">
            <x-slot:actions>
                <x-button href="{{ route('admin.users.index') }}" variant="ghost" icon="arrow-left">Kembali</x-button>
            </x-slot:actions>
        </x-page-header>

        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-6">
            @csrf
            @method('PUT')
            @include('admin.users._form', ['user' => $user])

            <div class="flex flex-wrap items-center justify-end gap-2">
                <x-button href="{{ route('admin.users.index') }}" variant="secondary">Batal</x-button>
                <x-button type="submit" icon="check">Simpan perubahan</x-button>
            </div>
        </form>
    </div>
@endsection
