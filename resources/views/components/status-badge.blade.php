@props(['status', 'label' => null, 'dot' => false])

@php
    $tone = \App\Support\StatusTone::for($status);
    $resolvedLabel = $label ?? (method_exists($status, 'label') ? $status->label() : ucfirst((string) $status));
@endphp

<x-badge :tone="$tone" :dot="$dot">{{ $resolvedLabel }}</x-badge>
