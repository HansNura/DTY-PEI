@extends('layouts.app')

@section('title', 'Packing Kardus')

@section('content')
    @include('inventory.packing._editor', [
        'summary' => $summary,
        'availableRolls' => $availableRolls,
        'carton' => null,
        'pageTitle' => 'Packing kardus',
        'pageDescription' => 'Scan 6 bobbin dengan nomor seri, merge, dan grade yang sama. Net weight, unit, grade, HU, dan tanggal dihitung otomatis.',
        'scanUrl' => route('warehouse.packing.scan'),
        'removeUrlBase' => url('/warehouse/packing/scan'),
        'resetUrl' => route('warehouse.packing.reset'),
        'submitUrl' => route('warehouse.packing.store'),
        'submitLabel' => 'Selesaikan packing',
    ])
@endsection
