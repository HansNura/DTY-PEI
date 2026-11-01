@extends('layouts.app')

@section('title', 'Pengiriman saya')

@section('content')
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Pengiriman saya</h1>
        <p class="mt-1 text-sm text-slate-500">Perbarui status perjalanan dan konfirmasi penerimaan bersama pelanggan.</p>
    </div>

    <div class="mt-6 space-y-3">
        @forelse ($orders as $order)
            <a href="{{ route('driver.deliveries.show', $order) }}" class="block rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-indigo-300">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="font-semibold text-slate-900">{{ $order->nomor_do }}</p>
                        <p class="mt-1 text-sm text-slate-600">{{ $order->customer->nama_pabrik }} · {{ $order->fleet->nomor_polisi }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $order->tanggal->format('d/m/Y') }}</p>
                    </div>
                    <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-200">{{ $order->status->label() }}</span>
                </div>
                @if ($order->proofOfDelivery)
                    <p class="mt-3 text-sm text-emerald-700">Diterima oleh {{ $order->proofOfDelivery->received_by_name }}</p>
                @endif
            </a>
        @empty
            <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-500">Belum ada pengiriman yang ditugaskan kepada Anda.</div>
        @endforelse
        @if ($orders->hasPages())
            <div class="pt-2">{{ $orders->links() }}</div>
        @endif
    </div>
@endsection
