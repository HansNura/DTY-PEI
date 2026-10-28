@php
    $isEdit = isset($user);
    $selectedRole = old('role', $isEdit ? $user->role->value : '');
    $isActive = old('is_active', $isEdit ? $user->is_active : true);
@endphp

<div class="grid gap-5 sm:grid-cols-2">
    <div>
        <label for="nama" class="block text-sm font-medium text-slate-700">Nama lengkap</label>
        <input id="nama" name="nama" type="text" value="{{ old('nama', $isEdit ? $user->nama : '') }}" required
               class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
    </div>

    <div>
        <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email', $isEdit ? $user->email : '') }}" required
               class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
    </div>

    <div class="sm:col-span-2">
        <label for="role" class="block text-sm font-medium text-slate-700">Peran &amp; hak akses</label>
        <select id="role" name="role" required
                class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
            <option value="">Pilih peran</option>
            @foreach ($roles as $role)
                <option value="{{ $role->value }}" @selected($selectedRole === $role->value)>{{ $role->label() }} &mdash; {{ $role->description() }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label for="password" class="block text-sm font-medium text-slate-700">
            Kata sandi {{ $isEdit ? '(opsional)' : '' }}
        </label>
        <input id="password" name="password" type="password" @required(! $isEdit) autocomplete="new-password"
               class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
        @if ($isEdit)
            <p class="mt-1 text-xs text-slate-500">Biarkan kosong jika tidak ingin mengubah kata sandi.</p>
        @endif
    </div>

    <div>
        <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Konfirmasi kata sandi</label>
        <input id="password_confirmation" name="password_confirmation" type="password" @required(! $isEdit) autocomplete="new-password"
               class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
    </div>

    <div class="sm:col-span-2">
        <label class="flex items-start gap-2.5 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" @checked($isActive)
                   class="mt-0.5 size-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
            <span>
                <span class="block text-sm font-medium text-slate-800">Akun aktif</span>
                <span class="block text-xs text-slate-500">Akun nonaktif tidak dapat masuk sampai diaktifkan kembali.</span>
            </span>
        </label>
    </div>
</div>
