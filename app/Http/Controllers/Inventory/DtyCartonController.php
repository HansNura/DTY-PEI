<?php

namespace App\Http\Controllers\Inventory;

use App\Enums\RollStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\CompletePackingRequest;
use App\Http\Requests\Inventory\UpdatePackedCartonRequest;
use App\Models\DtyCarton;
use App\Models\DtyRoll;
use App\Services\CartonPackingService;
use App\Services\PackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DtyCartonController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));
        $grade = trim((string) $request->query('grade'));
        $weightGrade = trim((string) $request->query('weight_grade'));
        $month = trim((string) $request->query('month'));

        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            $month = '';
        }

        if (! in_array($weightGrade, ['AX', 'AE', 'A', 'B', 'out'], true)) {
            $weightGrade = '';
        }

        $cartons = DtyCarton::query()
            ->with(['rolls', 'creator'])
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('nomor_hu', 'like', "%{$search}%")
                    ->orWhere('nomor_merge', 'like', "%{$search}%")
                    ->orWhere('pallet_number', 'like', "%{$search}%")
                    ->orWhere('grade', 'like', "%{$search}%");
            }))
            ->when($grade !== '', fn ($query) => $query->where('grade', $grade))
            ->when($weightGrade !== '', function ($query) use ($weightGrade): void {
                $average = '(net_weight_kg * 1000.0 / unit_bobbins)';

                match ($weightGrade) {
                    'AX' => $query->whereRaw("{$average} >= 5350 AND {$average} <= 5600"),
                    'AE' => $query->whereRaw("{$average} >= 5100 AND {$average} < 5350"),
                    'A' => $query->whereRaw("{$average} >= 1000 AND {$average} < 5100"),
                    'B' => $query->whereRaw("{$average} >= 500 AND {$average} < 1000"),
                    'out' => $query->whereRaw("({$average} < 500 OR {$average} > 5600)"),
                };
            })
            ->when($month !== '', fn ($query) => $query
                ->whereYear('production_date', substr($month, 0, 4))
                ->whereMonth('production_date', substr($month, 5, 2)))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('inventory.cartons.index', [
            'cartons' => $cartons,
            'grades' => DtyCarton::query()->select('grade')->distinct()->orderBy('grade')->pluck('grade'),
            'gradeCounts' => DtyCarton::query()
                ->selectRaw('grade, count(*) as total')
                ->groupBy('grade')
                ->pluck('total', 'grade'),
            'weightGradeCounts' => DtyCarton::query()
                ->selectRaw("
                    CASE
                        WHEN (net_weight_kg * 1000.0 / unit_bobbins) >= 5350 AND (net_weight_kg * 1000.0 / unit_bobbins) <= 5600 THEN 'AX'
                        WHEN (net_weight_kg * 1000.0 / unit_bobbins) >= 5100 AND (net_weight_kg * 1000.0 / unit_bobbins) < 5350 THEN 'AE'
                        WHEN (net_weight_kg * 1000.0 / unit_bobbins) >= 1000 AND (net_weight_kg * 1000.0 / unit_bobbins) < 5100 THEN 'A'
                        WHEN (net_weight_kg * 1000.0 / unit_bobbins) >= 500 AND (net_weight_kg * 1000.0 / unit_bobbins) < 1000 THEN 'B'
                        ELSE 'out'
                    END as bucket,
                    count(*) as total
                ")
                ->groupBy('bucket')
                ->pluck('total', 'bucket'),
            'search' => $search,
            'grade' => $grade,
            'weightGrade' => $weightGrade,
            'month' => $month,
            'summary' => [
                'cartons' => DtyCarton::count(),
                'bobbins' => (int) DtyCarton::sum('unit_bobbins'),
                'net_kg' => (float) DtyCarton::sum('net_weight_kg'),
                'gross_kg' => (float) DtyCarton::sum('gross_weight_kg'),
            ],
        ]);
    }

    public function edit(
        DtyCarton $carton,
        CartonPackingService $cartonPackingService,
        PackingService $packingService,
    ): View {
        $this->ensureEditable($carton);

        $carton->load('rolls');
        $cartonPackingService->initialize($carton);

        return view('inventory.cartons.edit', [
            'carton' => $carton,
            'summary' => $cartonPackingService->summary($carton),
            'availableRolls' => $packingService->availableRolls(),
        ]);
    }

    public function scanPacking(
        Request $request,
        DtyCarton $carton,
        CartonPackingService $cartonPackingService,
    ): JsonResponse {
        $this->ensureEditable($carton);

        $validated = $request->validate([
            'token' => ['required', 'string', 'max:255'],
        ]);

        $result = $cartonPackingService->scan($carton, $validated['token']);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function removePacking(
        DtyCarton $carton,
        DtyRoll $roll,
        CartonPackingService $cartonPackingService,
    ): JsonResponse {
        $this->ensureEditable($carton);

        return response()->json($cartonPackingService->remove($carton, $roll->id));
    }

    public function resetPacking(
        DtyCarton $carton,
        CartonPackingService $cartonPackingService,
    ): JsonResponse {
        $this->ensureEditable($carton);
        $cartonPackingService->reset($carton);

        return response()->json([
            'success' => true,
            'message' => 'Isi kardus dikembalikan ke susunan tersimpan.',
            'summary' => $cartonPackingService->summary($carton),
        ]);
    }

    public function updatePacking(
        CompletePackingRequest $request,
        DtyCarton $carton,
        CartonPackingService $cartonPackingService,
    ): RedirectResponse {
        $this->ensureEditable($carton);

        $carton = $cartonPackingService->finalize($carton, $request->validated(), $request->user());

        return redirect()->route('warehouse.cartons.label', $carton)
            ->with('status', "Isi kardus {$carton->nomor_hu} berhasil diperbarui.");
    }

    public function update(UpdatePackedCartonRequest $request, DtyCarton $carton): RedirectResponse
    {
        $this->ensureEditable($carton);

        $carton->update($request->validated());

        return redirect()->route('warehouse.cartons.index')
            ->with('status', "Kardus {$carton->nomor_hu} berhasil diperbarui.");
    }

    public function label(DtyCarton $carton): View
    {
        $carton->load('rolls');

        return view('inventory.cartons.label', [
            'carton' => $carton,
            'qrToken' => $carton->qr_token,
        ]);
    }

    private function ensureEditable(DtyCarton $carton): void
    {
        abort_unless(
            $carton->rolls()->where('status', '!=', RollStatus::InStock->value)->doesntExist(),
            403,
            'Kardus yang sudah dialokasikan tidak dapat diubah.',
        );
    }
}
