<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\CompletePackingRequest;
use App\Models\DtyRoll;
use App\Services\PackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PackingController extends Controller
{
    public function create(PackingService $packingService): View
    {
        return view('inventory.packing.create', [
            'summary' => $packingService->summary(),
            'availableRolls' => $packingService->availableRolls(),
        ]);
    }

    public function scan(Request $request, PackingService $packingService): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:255'],
        ]);

        $result = $packingService->scan($validated['token']);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function remove(DtyRoll $roll, PackingService $packingService): JsonResponse
    {
        return response()->json($packingService->remove($roll->id));
    }

    public function reset(PackingService $packingService): JsonResponse
    {
        $packingService->reset();

        return response()->json([
            'success' => true,
            'message' => 'Daftar packing dikosongkan.',
            'summary' => $packingService->summary(),
        ]);
    }

    public function store(CompletePackingRequest $request, PackingService $packingService): RedirectResponse
    {
        $carton = $packingService->finalize($request->validated(), $request->user());

        return redirect()->route('warehouse.cartons.label', $carton)
            ->with('status', "Kardus {$carton->nomor_hu} berhasil dipacking.");
    }
}
