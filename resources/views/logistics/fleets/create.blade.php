@extends('layouts.app')

@section('title', 'Tambah Armada')

@section('content')
    <div class="mx-auto max-w-2xl">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Tambah armada</h1>
                <p class="mt-1 text-sm text-slate-500">Catat identitas kendaraan dan pengemudi utama.</p>
            </div>
            <a href="{{ route('logistics.fleets.index') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">Kembali</a>
        </div>
        <form method="POST" action="{{ route('logistics.fleets.store') }}" class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            @include('logistics.fleets._form')
            <div class="mt-6 flex items-center justify-end gap-2">
                <a href="{{ route('logistics.fleets.index') }}" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-900">Batal</a>
                <button type="submit" class="rounded-lg bg-indigo-700 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-indigo-800">Simpan armada</button>
            </div>
        </form>
    </div>
@endsection
