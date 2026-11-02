@extends('layouts.app')

@section('title', 'Beranda')

@section('content')
    <div class="space-y-20 pb-10">
        <section class="grid items-center gap-12 lg:grid-cols-[1.05fr_0.95fr]">
            <div>
                <x-badge tone="brand" dot>Pelacakan DTY end-to-end</x-badge>

                <h1 class="mt-5 text-4xl font-semibold tracking-tight text-slate-900 sm:text-5xl">
                    Setiap rol benang,<br>
                    <span class="text-brand-700">terlacak sampai diterima.</span>
                </h1>

                <p class="mt-5 max-w-xl text-base leading-relaxed text-slate-600">
                    PMT menyatukan gudang, checker, driver, dan tim logistik dalam satu alur:
                    master rol dan QR, surat jalan, validasi muatan, live tracking, hingga bukti terima.
                </p>

                <div class="mt-8 flex flex-wrap items-center gap-3">
                    @auth
                        <x-button href="{{ route('dashboard') }}" icon-right="arrow-right">Buka dashboard</x-button>
                    @else
                        <x-button href="{{ route('login') }}" icon-right="arrow-right">Masuk ke sistem</x-button>
                    @endauth
                    <x-button href="#alur" variant="secondary" icon="route">Lihat alur kerja</x-button>
                </div>

                <div class="mt-10 grid max-w-lg grid-cols-3 gap-4 border-t border-slate-200 pt-6">
                    <div>
                        <p class="text-2xl font-semibold tabular-nums text-slate-900">4</p>
                        <p class="text-xs text-slate-500">peran terintegrasi</p>
                    </div>
                    <div>
                        <p class="text-2xl font-semibold tabular-nums text-slate-900">1 QR</p>
                        <p class="text-xs text-slate-500">per gulungan</p>
                    </div>
                    <div>
                        <p class="text-2xl font-semibold tabular-nums text-slate-900">Live</p>
                        <p class="text-xs text-slate-500">posisi armada</p>
                    </div>
                </div>
            </div>

            {{-- Product preview --}}
            <div class="relative">
                <div class="absolute -inset-6 rounded-[2rem] bg-gradient-to-br from-brand-100 via-white to-sky-50 blur-2xl"></div>
                <div class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-card-hover">
                    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
                        <div class="flex items-center gap-2">
                            <span class="size-2.5 rounded-full bg-rose-300"></span>
                            <span class="size-2.5 rounded-full bg-amber-300"></span>
                            <span class="size-2.5 rounded-full bg-emerald-300"></span>
                        </div>
                        <span class="text-[10px] font-medium uppercase tracking-wider text-slate-400">PMT Dashboard</span>
                    </div>

                    <div class="grid grid-cols-[64px_1fr]">
                        <div class="border-r border-slate-100 bg-slate-50/60 p-3">
                            <div class="mx-auto size-8 rounded-lg bg-brand-600"></div>
                            <div class="mt-5 space-y-3">
                                <div class="h-6 rounded-md bg-brand-100"></div>
                                <div class="h-6 rounded-md bg-slate-200/70"></div>
                                <div class="h-6 rounded-md bg-slate-200/70"></div>
                                <div class="h-6 rounded-md bg-slate-200/70"></div>
                            </div>
                        </div>
                        <div class="space-y-4 p-4">
                            <div class="flex items-center justify-between">
                                <div class="h-2.5 w-24 rounded-full bg-slate-200"></div>
                                <div class="h-6 w-16 rounded-full bg-brand-50"></div>
                            </div>
                            <div class="grid grid-cols-3 gap-2">
                                <div class="rounded-xl border border-slate-100 p-3">
                                    <div class="h-2 w-10 rounded-full bg-slate-200"></div>
                                    <div class="mt-2 h-4 w-8 rounded bg-slate-800"></div>
                                </div>
                                <div class="rounded-xl border border-slate-100 p-3">
                                    <div class="h-2 w-10 rounded-full bg-slate-200"></div>
                                    <div class="mt-2 h-4 w-8 rounded bg-slate-800"></div>
                                </div>
                                <div class="rounded-xl border border-slate-100 p-3">
                                    <div class="h-2 w-10 rounded-full bg-slate-200"></div>
                                    <div class="mt-2 h-4 w-8 rounded bg-slate-800"></div>
                                </div>
                            </div>
                            <div class="rounded-xl border border-slate-100 p-3">
                                <div class="flex items-center justify-between">
                                    <div class="h-2.5 w-28 rounded-full bg-slate-200"></div>
                                    <div class="h-5 w-14 rounded-full bg-emerald-50"></div>
                                </div>
                                <div class="mt-3 h-2 w-full rounded-full bg-slate-100">
                                    <div class="h-2 w-2/3 rounded-full bg-brand-500"></div>
                                </div>
                                <div class="mt-4 space-y-2">
                                    <div class="h-2 w-full rounded-full bg-slate-100"></div>
                                    <div class="h-2 w-5/6 rounded-full bg-slate-100"></div>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div class="h-16 rounded-xl bg-gradient-to-br from-brand-50 to-sky-50"></div>
                                <div class="h-16 rounded-xl bg-slate-50"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="alur">
            <div class="max-w-2xl">
                <x-badge tone="neutral">Alur kerja</x-badge>
                <h2 class="mt-3 text-2xl font-semibold tracking-tight text-slate-900">Dari gudang sampai tanda terima</h2>
                <p class="mt-2 text-sm text-slate-600">Satu alur yang sama dipakai semua peran, tanpa input ganda.</p>
            </div>

            <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                @foreach ([
                    ['package', 'Input rol DTY', 'Denier, filamen, berat, dan nomor lot.'],
                    ['qr-code', 'Cetak QR', 'Satu QR unik per gulungan.'],
                    ['clipboard', 'Terbitkan DO', 'Pilih pelanggan, armada, dan rol.'],
                    ['scan', 'Scan & berangkat', 'Checker memvalidasi muatan.'],
                    ['map-pin', 'Bukti terima', 'Posisi dan POD tercatat.'],
                ] as $index => [$icon, $title, $description])
                    <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-card">
                        <div class="flex items-center justify-between">
                            <span class="grid size-10 place-items-center rounded-xl bg-brand-50 text-brand-700">
                                <x-icon :name="$icon" class="size-5" />
                            </span>
                            <span class="text-xs font-semibold tabular-nums text-slate-300">0{{ $index + 1 }}</span>
                        </div>
                        <p class="mt-4 text-sm font-semibold text-slate-900">{{ $title }}</p>
                        <p class="mt-1 text-xs leading-relaxed text-slate-500">{{ $description }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <section>
            <div class="max-w-2xl">
                <x-badge tone="neutral">Per peran</x-badge>
                <h2 class="mt-3 text-2xl font-semibold tracking-tight text-slate-900">Satu sistem, empat fokus kerja</h2>
            </div>

            <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['users', 'Administrator', 'Akun, hak akses, dan audit sistem.', 'bg-brand-50 text-brand-700'],
                    ['package', 'Admin Gudang', 'Master DTY, QR label, dan validasi muatan.', 'bg-amber-50 text-amber-700'],
                    ['route', 'Tim Logistik', 'Pelanggan, armada, surat jalan, dan tracking.', 'bg-sky-50 text-sky-700'],
                    ['truck', 'Driver', 'Status perjalanan dan bukti terima.', 'bg-emerald-50 text-emerald-700'],
                ] as [$icon, $role, $description, $toneClass])
                    <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-card">
                        <span class="grid size-10 place-items-center rounded-xl {{ $toneClass }}">
                            <x-icon :name="$icon" class="size-5" />
                        </span>
                        <p class="mt-4 text-sm font-semibold text-slate-900">{{ $role }}</p>
                        <p class="mt-1 text-xs leading-relaxed text-slate-500">{{ $description }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="overflow-hidden rounded-3xl bg-slate-900 px-6 py-10 sm:px-10">
            <div class="flex flex-wrap items-center justify-between gap-6">
                <div class="max-w-xl">
                    <h2 class="text-2xl font-semibold tracking-tight text-white">Siap memulai?</h2>
                    <p class="mt-2 text-sm text-slate-300">Masuk dengan akun yang diberikan administrator untuk membuka dashboard sesuai peran Anda.</p>
                </div>
                <x-button href="{{ route('login') }}" variant="primary" size="lg" icon-right="arrow-right">Masuk sekarang</x-button>
            </div>
        </section>
    </div>
@endsection
