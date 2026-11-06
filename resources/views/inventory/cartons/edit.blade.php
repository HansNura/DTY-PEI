@extends('layouts.app')

@section('title', 'Edit Kardus DTY')

@section('content')
    @include('inventory.packing._editor', [
        'summary' => $summary,
        'availableRolls' => $availableRolls,
        'carton' => $carton,
        'pageTitle' => 'Edit kardus DTY',
        'pageDescription' => 'Scan ulang isi kardus. Keluarkan bobbin yang diganti, lalu scan pengganti dengan merge dan grade yang sama.',
        'scanUrl' => route('warehouse.cartons.packing.scan', $carton),
        'removeUrlBase' => url('/warehouse/cartons/'.$carton->id.'/packing/scan'),
        'resetUrl' => route('warehouse.cartons.packing.reset', $carton),
        'submitUrl' => route('warehouse.cartons.packing.update', $carton),
        'submitLabel' => 'Simpan perubahan packing',
    ])
@endsection
