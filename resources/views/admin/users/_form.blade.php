@php
    $isEdit = isset($user);
    $selectedRole = old('role', $isEdit ? $user->role->value : '');
    $isActive = old('is_active', $isEdit ? $user->is_active : true);
@endphp

<div class="space-y-6">
    <x-card title="Identitas" description="Nama dan email digunakan untuk masuk ke sistem">
        <div class="grid gap-5 sm:grid-cols-2">
            <x-field label="Nama lengkap" name="nama" required>
                <x-input id="nama" name="nama" type="text" value="{{ old('nama', $isEdit ? $user->nama : '') }}" required />
            </x-field>

            <x-field label="Email" name="email" required>
                <x-input id="email" name="email" type="email" value="{{ old('email', $isEdit ? $user->email : '') }}" required />
            </x-field>
        </div>
    </x-card>

    <x-card title="Akses" description="Peran menentukan modul yang dapat dibuka pengguna">
        <div class="space-y-5">
            <x-field label="Peran & hak akses" name="role" required>
                <x-select id="role" name="role" required>
                    <option value="">Pilih peran</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->value }}" @selected($selectedRole === $role->value)>{{ $role->label() }} — {{ $role->description() }}</option>
                    @endforeach
                </x-select>
            </x-field>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-field :label="'Kata sandi '.($isEdit ? '(opsional)' : '')" name="password" :required="! $isEdit" :hint="$isEdit ? 'Biarkan kosong jika tidak ingin mengubah kata sandi.' : 'Minimal 8 karakter.'">
                    <x-input id="password" name="password" type="password" @required(! $isEdit) autocomplete="new-password" />
                </x-field>

                <x-field label="Konfirmasi kata sandi" name="password_confirmation" :required="! $isEdit">
                    <x-input id="password_confirmation" name="password_confirmation" type="password" @required(! $isEdit) autocomplete="new-password" />
                </x-field>
            </div>

            <div>
                <input type="hidden" name="is_active" value="0">
                <x-checkbox name="is_active" value="1" :checked="$isActive" label="Akun aktif" description="Akun nonaktif tidak dapat masuk sampai diaktifkan kembali." />
            </div>
        </div>
    </x-card>
</div>
