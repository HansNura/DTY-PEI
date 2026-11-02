@extends('layouts.app')

@section('title', 'Profil')

@section('content')
    <div class="mx-auto max-w-4xl space-y-6">
        <x-page-header title="Profil saya" description="Perbarui data akun dan keamanan akun Anda." />

        <div class="grid gap-6 lg:grid-cols-[1fr_1.2fr]">
            <x-card>
                <div class="flex items-center gap-4">
                    <x-avatar :name="$user->nama" size="size-14" />
                    <div class="min-w-0">
                        <p class="truncate text-base font-semibold text-slate-900">{{ $user->nama }}</p>
                        <p class="truncate text-sm text-slate-500">{{ $user->email }}</p>
                        <div class="mt-2"><x-status-badge :status="$user->role" /></div>
                    </div>
                </div>

                <dl class="mt-6 space-y-3 border-t border-slate-100 pt-5 text-sm">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-500">Status akun</dt>
                        <dd><x-badge :tone="$user->is_active ? 'success' : 'danger'" dot>{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge></dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-500">Login terakhir</dt>
                        <dd class="font-medium text-slate-900">{{ $user->last_login_at?->translatedFormat('d M Y, H:i') ?? 'Belum pernah' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-500">Bergabung</dt>
                        <dd class="font-medium text-slate-900">{{ $user->created_at?->translatedFormat('d M Y') }}</dd>
                    </div>
                </dl>
            </x-card>

            <div class="space-y-6">
                <form method="POST" action="{{ route('profile.update') }}">
                    @csrf
                    @method('PUT')

                    <x-card title="Data akun" description="Nama dan email yang digunakan untuk masuk">
                        <div class="grid gap-5 sm:grid-cols-2">
                            <x-field label="Nama lengkap" name="nama" required>
                                <x-input id="nama" name="nama" type="text" value="{{ old('nama', $user->nama) }}" required />
                            </x-field>

                            <x-field label="Email" name="email" required>
                                <x-input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required />
                            </x-field>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <x-button type="submit">Simpan data</x-button>
                        </div>
                    </x-card>
                </form>

                <form method="POST" action="{{ route('profile.password') }}">
                    @csrf
                    @method('PUT')

                    <x-card title="Keamanan" description="Gunakan kata sandi yang kuat dan tidak dipakai ulang">
                        <div class="space-y-5">
                            <x-field label="Kata sandi saat ini" name="current_password" required>
                                <x-input id="current_password" name="current_password" type="password" required autocomplete="current-password" />
                            </x-field>

                            <div class="grid gap-5 sm:grid-cols-2">
                                <x-field label="Kata sandi baru" name="password" required hint="Minimal 8 karakter.">
                                    <x-input id="password" name="password" type="password" required autocomplete="new-password" />
                                </x-field>
                                <x-field label="Konfirmasi kata sandi baru" name="password_confirmation" required>
                                    <x-input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" />
                                </x-field>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <x-button type="submit" variant="secondary">Ubah kata sandi</x-button>
                        </div>
                    </x-card>
                </form>
            </div>
        </div>
    </div>
@endsection
