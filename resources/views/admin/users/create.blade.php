@extends('layouts.app')

@section('title', 'Tambah Pengguna')

@section('content')
    <div class="mx-auto max-w-2xl">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Tambah pengguna</h1>
                <p class="mt-1 text-sm text-slate-500">Buat akun baru beserta peran dan hak aksesnya.</p>
            </div>
            <a href="{{ route('admin.users.index') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">Kembali</a>
        </div>

        <form method="POST" action="{{ route('admin.users.store') }}" class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            @include('admin.users._form')

            <div class="mt-6 flex items-center justify-end gap-2">
                <a href="{{ route('admin.users.index') }}" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-900">Batal</a>
                <button type="submit" class="rounded-lg bg-indigo-700 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-indigo-800">
                    Simpan pengguna
                </button>
            </div>
        </form>
    </div>
@endsection
