@props(['status'])

@php
    $steps = [
        \App\Enums\DeliveryOrderStatus::Issued->value => 'Diterbitkan',
        \App\Enums\DeliveryOrderStatus::Loading->value => 'Pemuatan',
        \App\Enums\DeliveryOrderStatus::Departed->value => 'Berangkat',
        \App\Enums\DeliveryOrderStatus::InTransit->value => 'Dalam Perjalanan',
        \App\Enums\DeliveryOrderStatus::Arrived->value => 'Tiba',
        \App\Enums\DeliveryOrderStatus::Received->value => 'Diterima',
    ];

    $current = array_search($status->value, array_keys($steps), true);
    $cancelled = $status === \App\Enums\DeliveryOrderStatus::Cancelled;
@endphp

@if ($cancelled)
    <x-alert tone="danger" title="Surat Jalan dibatalkan">
        Pengiriman ini tidak lagi aktif dan tidak dapat diproses.
    </x-alert>
@else
    <ol {{ $attributes->merge(['class' => 'flex items-start gap-1 overflow-x-auto pb-1']) }}>
        @foreach ($steps as $value => $label)
            @php
                $index = array_search($value, array_keys($steps), true);
                $done = $current !== false && $index <= $current;
                $active = $index === $current;
            @endphp
            <li class="flex min-w-0 flex-1 items-center gap-1.5">
                <span class="grid size-6 shrink-0 place-items-center rounded-full text-[10px] font-semibold {{ $done ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-400' }}">
                    @if ($done && ! $active)
                        <x-icon name="check" class="size-3.5" />
                    @else
                        {{ $index + 1 }}
                    @endif
                </span>
                <span class="truncate text-xs {{ $active ? 'font-semibold text-slate-900' : 'text-slate-500' }}">{{ $label }}</span>
                @if (! $loop->last)
                    <span class="hidden h-px flex-1 bg-slate-200 sm:block"></span>
                @endif
            </li>
        @endforeach
    </ol>
@endif
