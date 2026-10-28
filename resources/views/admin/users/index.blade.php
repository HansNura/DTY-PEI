@extends('layouts.app')

@section('title', 'Pengguna')

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Manajemen pengguna</h1>
            <p class="mt-1 text-sm text-slate-500">Kelola akun dan hak akses Administrator, Admin Gudang, Tim Logistik, dan Driver.</p>
        </div>
        <a href="{{ route('admin.users.create') }}" class="rounded-lg bg-indigo-700 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-indigo-800">
            Tambah pengguna
        </a>
    </div>

    <form method="GET" action="{{ route('admin.users.index') }}" class="mt-6 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-[1.6fr_1fr_1fr_auto]">
        <div>
            <label for="q" class="sr-only">Cari</label>
            <input id="q" name="q" type="search" value="{{ $search }}" placeholder="Cari nama atau email"
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
        </div>
        <div>
            <label for="role" class="sr-only">Peran</label>
            <select id="role" name="role" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                <option value="">Semua peran</option>
                @foreach ($roles as $roleOption)
                    <option value="{{ $roleOption->value }}" @selected($role === $roleOption->value)>{{ $roleOption->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="status" class="sr-only">Status</label>
            <select id="status" name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                <option value="">Semua status</option>
                <option value="active" @selected($status === 'active')>Aktif</option>
                <option value="inactive" @selected($status === 'inactive')>Nonaktif</option>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                Filter
            </button>
            <a href="{{ route('admin.users.index') }}" class="rounded-lg px-3 py-2 text-sm text-slate-500 hover:text-slate-800">Reset</a>
        </div>
    </form>

    <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-medium">Nama</th>
                        <th class="px-5 py-3 font-medium">Peran</th>
                        <th class="px-5 py-3 font-medium">Status</th>
                        <th class="px-5 py-3 font-medium">Login terakhir</th>
                        <th class="px-5 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($users as $user)
                        <tr>
                            <td class="px-5 py-3">
                                <p class="font-medium text-slate-900">{{ $user->nama }}</p>
                                <p class="text-xs text-slate-500">{{ $user->email }}</p>
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $user->role->badgeClasses() }}">
                                    {{ $user->role->label() }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                @if ($user->is_active)
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-200">Aktif</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700 ring-1 ring-inset ring-rose-200">Nonaktif</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-slate-600">{{ $user->last_login_at?->translatedFormat('d M Y, H:i') ?? 'Belum pernah' }}</td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-3">
                                    <a href="{{ route('admin.users.edit', $user) }}" class="text-sm font-medium text-indigo-700 hover:text-indigo-900">Edit</a>

                                    @unless ($user->is(auth()->user()))
                                        <form method="POST" action="{{ route('admin.users.toggle-active', $user) }}"
                                              x-data
                                              x-on:submit="if (! confirm('{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }} pengguna {{ $user->nama }}?')) $event.preventDefault()">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-sm font-medium {{ $user->is_active ? 'text-rose-700 hover:text-rose-900' : 'text-emerald-700 hover:text-emerald-900' }}">
                                                {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-slate-500">Tidak ada pengguna yang cocok dengan filter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="border-t border-slate-200 px-5 py-4">
                {{ $users->links() }}
            </div>
        @endif
    </div>
@endsection
