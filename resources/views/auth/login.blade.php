@extends('layouts.app')

@section('title', 'Masuk')

@section('content')
    <div class="mx-auto grid max-w-5xl overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-card lg:grid-cols-2">
        <div class="relative hidden overflow-hidden bg-gradient-to-br from-brand-700 via-brand-600 to-teal-500 p-10 text-white lg:flex lg:flex-col lg:justify-between">
            <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 22px 22px;"></div>

            <div class="relative">
                <div class="flex items-center gap-2.5">
                    <span class="grid size-10 place-items-center rounded-xl bg-white/15 text-white ring-1 ring-white/20">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
                            <path d="M8 4h8M8 20h8M9 4v16M15 4v16" />
                            <path d="M6 8h3M15 8h3M6 12h3M15 12h3M6 16h3M15 16h3" />
                        </svg>
                    </span>
                    <div>
                        <p class="text-sm font-semibold">PMT</p>
                        <p class="text-xs text-white/70">Pelacakan DTY</p>
                    </div>
                </div>

                <h1 class="mt-12 text-3xl font-semibold tracking-tight">Kendali penuh atas setiap perjalanan rol benang.</h1>
                <p class="mt-3 text-sm leading-relaxed text-white/75">Gudang, checker, driver, dan logistik bekerja dari data yang sama.</p>
            </div>

            <ul class="relative mt-10 space-y-3 text-sm text-white/85">
                @foreach ([
                    'Validasi muatan dengan QR per gulungan',
                    'Status perjalanan dan posisi armada',
                    'Riwayat scan dan perjalanan yang dapat diaudit',
                ] as $item)
                    <li class="flex items-center gap-2.5">
                        <span class="grid size-5 place-items-center rounded-full bg-white/15">
                            <x-icon name="check" class="size-3" />
                        </span>
                        {{ $item }}
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="p-6 sm:p-10"
             data-initial-email="{{ old('email') }}"
             x-data="{
                email: '',
                password: '',
                demoOpen: false,
                selectedRole: null,
                selectAccount(role, email) {
                    this.email = email;
                    this.password = 'password';
                    this.selectedRole = role;
                    this.demoOpen = true;
                    this.$nextTick(() => this.$refs.loginButton?.focus());
                }
             }"
             x-init="email = $el.dataset.initialEmail || ''">
            <h2 class="text-2xl font-semibold tracking-tight text-slate-900">Masuk ke PMT</h2>
            <p class="mt-1 text-sm text-slate-500">Gunakan akun yang diberikan administrator.</p>

            <template x-if="selectedRole">
                <div class="mt-4 flex flex-wrap items-center justify-between gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    <span>
                        Akun <strong x-text="selectedRole"></strong> dipilih:
                        <span class="font-mono" x-text="email"></span>
                        <span class="text-emerald-600">/</span>
                        <span class="font-mono">password</span>
                    </span>
                    <button type="button" class="text-xs font-semibold underline" x-on:click="demoOpen = true">Ganti akun</button>
                </div>
            </template>

            <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
                @csrf

                <x-field label="Email" name="email" required>
                    <x-input id="email" name="email" type="email" x-model="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="nama@perusahaan.com" />
                </x-field>

                <x-field label="Kata sandi" name="password" required>
                    <x-input id="password" name="password" type="password" x-model="password" required autocomplete="current-password" placeholder="••••••••" />
                </x-field>

                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remember" value="1" class="size-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                    Ingat saya di perangkat ini
                </label>

                <x-button type="submit" class="w-full" size="lg" x-ref="loginButton">Masuk</x-button>
            </form>

            <div class="mt-8 border-t border-slate-100 pt-5">
                <button type="button" class="flex items-center gap-1.5 text-sm font-medium text-brand-700 hover:text-brand-800" x-on:click="demoOpen = ! demoOpen">
                    <x-icon name="users" class="size-4" />
                    <span x-show="! demoOpen">Lihat akun demo</span>
                    <span x-show="demoOpen" x-cloak>Sembunyikan akun demo</span>
                </button>

                <div class="mt-3 grid gap-2 sm:grid-cols-2" x-cloak x-show="demoOpen">
                    @foreach ([
                        ['Administrator', 'admin@pmt.test'],
                        ['Admin Gudang', 'gudang@pmt.test'],
                        ['Tim Logistik', 'logistik@pmt.test'],
                        ['Driver', 'driver@pmt.test'],
                    ] as [$role, $email])
                        <button type="button" x-on:click="selectAccount('{{ $role }}', '{{ $email }}')"
                                class="group rounded-xl border px-3 py-2.5 text-left transition"
                                x-bind:class="selectedRole === '{{ $role }}' ? 'border-brand-300 bg-brand-50 ring-1 ring-brand-200' : 'border-slate-200 bg-slate-50/70 hover:border-brand-200 hover:bg-brand-50/40'">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-xs font-semibold text-slate-900">{{ $role }}</span>
                                <span class="text-[10px] font-medium text-brand-700" x-show="selectedRole === '{{ $role }}'" x-cloak>Dipilih</span>
                                <x-icon name="arrow-right" class="size-3.5 text-slate-300 transition group-hover:text-brand-600" x-show="selectedRole !== '{{ $role }}'" />
                            </div>
                            <p class="mt-1 truncate font-mono text-[11px] text-slate-700">{{ $email }}</p>
                            <p class="mt-0.5 font-mono text-[11px] text-slate-500">password: <span class="font-semibold text-slate-700">password</span></p>
                        </button>
                    @endforeach

                    <p class="text-xs text-slate-500 sm:col-span-2">
                        Klik salah satu akun untuk mengisi email dan kata sandi otomatis, lalu klik <strong>Masuk</strong>.
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
