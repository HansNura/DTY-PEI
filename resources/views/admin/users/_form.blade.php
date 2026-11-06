@php
    $isEdit = isset($user);
    $isSelf = $isEdit && $user->is(auth()->user());
    $selectedRole = old('role', $isEdit ? $user->role->value : '');
    $isActive = (bool) old('is_active', $isEdit ? $user->is_active : true);
    $roleIcons = [
        'admin' => 'users',
        'admin_gudang' => 'boxes',
        'logistik' => 'clipboard',
        'driver' => 'truck',
    ];
@endphp

<div class="space-y-6" x-data="{ showPassword: false, showConfirmation: false }">
    @if ($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <p class="font-semibold">Periksa kembali data pengguna.</p>
            <ul class="mt-1 list-disc space-y-0.5 pl-4 text-xs">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($isEdit)
        <x-card title="Akun saat ini" description="Ringkasan identitas dan status akun yang sedang diedit.">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex min-w-0 items-center gap-3">
                    <x-avatar :name="$user->nama" size="size-12" />
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-slate-900">{{ $user->nama }}</p>
                        <p class="truncate text-xs text-slate-500">{{ $user->email }}</p>
                        <div class="mt-2 flex flex-wrap items-center gap-1.5">
                            <x-status-badge :status="$user->role" />
                            <x-badge :tone="$user->is_active ? 'success' : 'danger'" dot>{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge>
                        </div>
                    </div>
                </div>
                <div class="text-right text-xs text-slate-500">
                    <p class="text-[10px] uppercase tracking-wide text-slate-400">Login terakhir</p>
                    <p class="mt-1 font-medium text-slate-700">{{ $user->last_login_at?->translatedFormat('d M Y, H:i') ?? 'Belum pernah' }}</p>
                </div>
            </div>

            @if ($isSelf)
                <p class="mt-4 rounded-xl bg-amber-50 px-3.5 py-2.5 text-xs text-amber-800 ring-1 ring-inset ring-amber-100">
                    Anda sedang mengedit akun sendiri. Peran dan status aktif dikunci agar akses administrator tidak hilang.
                </p>
            @endif
        </x-card>
    @endif

    <x-card title="Identitas" description="Nama dan email digunakan untuk masuk ke sistem.">
        <div class="grid gap-5 sm:grid-cols-2">
            <x-field label="Nama lengkap" name="nama" required>
                <x-input id="nama" name="nama" type="text" value="{{ old('nama', $isEdit ? $user->nama : '') }}" placeholder="Contoh: Budi Santoso" required />
            </x-field>

            <x-field label="Email" name="email" required hint="Gunakan email aktif. Email disimpan dalam huruf kecil.">
                <x-input id="email" name="email" type="email" value="{{ old('email', $isEdit ? $user->email : '') }}" placeholder="Contoh: budi@perusahaan.com" required />
            </x-field>
        </div>
    </x-card>

    <x-card title="Peran & akses" description="Pilih satu peran. Menu dan tindakan mengikuti peran tersebut.">
        @if ($isSelf)
            <input type="hidden" name="role" value="{{ \App\Enums\UserRole::Admin->value }}">
        @endif

        <div class="grid gap-2.5 sm:grid-cols-2">
            @foreach ($roles as $roleOption)
                <label class="relative cursor-pointer select-none">
                    <input type="radio" name="role" value="{{ $roleOption->value }}" class="peer sr-only"
                           @checked($selectedRole === $roleOption->value) @disabled($isSelf)>
                    <span class="flex h-full items-start gap-3 rounded-xl border border-slate-200 bg-white p-4 text-left transition hover:border-slate-300 hover:bg-slate-50/60 peer-checked:border-brand-500 peer-checked:bg-brand-50/60 peer-checked:ring-2 peer-checked:ring-brand-500/10 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500/40 peer-disabled:cursor-not-allowed peer-disabled:opacity-60">
                        <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-slate-100 text-slate-600">
                            <x-icon :name="$roleIcons[$roleOption->value] ?? 'user'" class="size-4" />
                        </span>
                        <span class="min-w-0">
                            <span class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                                {{ $roleOption->label() }}
                            </span>
                            <span class="mt-1 block text-xs leading-relaxed text-slate-500">{{ $roleOption->description() }}</span>
                        </span>
                    </span>
                </label>
            @endforeach
        </div>

        @error('role')
            <p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p>
        @enderror

        @if ($isSelf)
            <p class="mt-2 text-xs text-slate-500">Peran akun sendiri tidak dapat diubah dari halaman ini.</p>
        @endif
    </x-card>

    <x-card :title="$isEdit ? 'Ubah kata sandi' : 'Kata sandi'" :description="$isEdit ? 'Kosongkan jika kata sandi tidak ingin diubah.' : 'Buat kata sandi awal untuk pengguna baru.'">
        <div class="grid gap-5 sm:grid-cols-2">
            <x-field :label="'Kata sandi '.($isEdit ? '(opsional)' : '')" name="password" :required="! $isEdit" hint="Minimal 8 karakter.">
                <div class="relative">
                    <input id="password" name="password" type="password" @required(! $isEdit) autocomplete="new-password"
                           placeholder="{{ $isEdit ? 'Kosongkan jika tidak diubah' : 'Minimal 8 karakter' }}"
                           x-bind:type="showPassword ? 'text' : 'password'"
                           class="block w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 pr-20 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 transition focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 focus:outline-none" />
                    <button type="button" x-on:click="showPassword = ! showPassword"
                            class="absolute inset-y-0 right-2 my-auto h-7 rounded-lg px-2 text-[10px] font-semibold text-slate-500 hover:bg-slate-100 hover:text-slate-700"
                            aria-label="Tampilkan atau sembunyikan kata sandi">
                        <span x-text="showPassword ? 'Sembunyikan' : 'Lihat'"></span>
                    </button>
                </div>
            </x-field>

            <x-field label="Konfirmasi kata sandi" name="password_confirmation" :required="! $isEdit" hint="Ulangi kata sandi dengan tepat.">
                <div class="relative">
                    <input id="password_confirmation" name="password_confirmation" type="password" @required(! $isEdit) autocomplete="new-password"
                           placeholder="Ulangi kata sandi"
                           x-bind:type="showConfirmation ? 'text' : 'password'"
                           class="block w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 pr-20 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 transition focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 focus:outline-none" />
                    <button type="button" x-on:click="showConfirmation = ! showConfirmation"
                            class="absolute inset-y-0 right-2 my-auto h-7 rounded-lg px-2 text-[10px] font-semibold text-slate-500 hover:bg-slate-100 hover:text-slate-700"
                            aria-label="Tampilkan atau sembunyikan konfirmasi kata sandi">
                        <span x-text="showConfirmation ? 'Sembunyikan' : 'Lihat'"></span>
                    </button>
                </div>
            </x-field>
        </div>
    </x-card>

    <x-card title="Status akun" description="Akun nonaktif tidak dapat masuk sampai diaktifkan kembali.">
        @if ($isSelf)
            <input type="hidden" name="is_active" value="1">
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3">
                <div>
                    <p class="text-sm font-semibold text-emerald-800">Akun aktif</p>
                    <p class="mt-0.5 text-xs text-emerald-700">Status akun sendiri tidak dapat dinonaktifkan.</p>
                </div>
                <x-badge tone="success" dot>Aktif</x-badge>
            </div>
        @else
            <input type="hidden" name="is_active" value="0">
            <x-checkbox name="is_active" value="1" :checked="$isActive" label="Akun aktif" description="Izinkan pengguna masuk ke sistem setelah akun disimpan." />
            @error('is_active')
                <p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p>
            @enderror
        @endif
    </x-card>
</div>
