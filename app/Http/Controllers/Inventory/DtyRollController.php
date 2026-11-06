<?php

namespace App\Http\Controllers\Inventory;

use App\Enums\RollStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\WeighDtyRollRequest;
use App\Models\DtyRoll;
use App\Services\BobbinWeighingService;
use App\Support\BobbinWeightStandard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DtyRollController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));
        $status = (string) $request->query('status');

        $rolls = DtyRoll::query()
            ->with(['carton', 'productionBatch'])
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('kode_rol', 'like', "%{$search}%")
                    ->orWhere('nomor_seri', 'like', "%{$search}%")
                    ->orWhere('nomor_lot', 'like', "%{$search}%")
                    ->orWhereHas('carton', fn ($cartonQuery) => $cartonQuery
                        ->where('nomor_hu', 'like', "%{$search}%")
                        ->orWhere('nomor_merge', 'like', "%{$search}%"));
            }))
            ->when(RollStatus::tryFrom($status) !== null, fn ($query) => $query->where('status', $status))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $statusCounts = DtyRoll::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $availableWeight = (float) DtyRoll::query()
            ->whereIn('status', [RollStatus::ReadyToPack, RollStatus::InStock])
            ->sum('berat_gram');

        $summary = [
            'total' => (int) $statusCounts->sum(),
            'ready_to_pack' => (int) ($statusCounts[RollStatus::ReadyToPack->value] ?? 0),
            'in_stock' => (int) ($statusCounts[RollStatus::InStock->value] ?? 0),
            'allocated' => (int) ($statusCounts[RollStatus::Allocated->value] ?? 0),
            'loaded' => (int) ($statusCounts[RollStatus::Loaded->value] ?? 0),
            'delivered' => (int) ($statusCounts[RollStatus::Delivered->value] ?? 0),
            'cancelled' => (int) ($statusCounts[RollStatus::Cancelled->value] ?? 0),
            'available_weight_kg' => round($availableWeight / 1000, 2),
        ];

        return view('inventory.rolls.index', [
            'rolls' => $rolls,
            'statusLabel' => RollStatus::tryFrom($status)?->label(),
            'summary' => $summary,
            'search' => $search,
            'status' => $status,
        ]);
    }

    public function create(BobbinWeighingService $weighingService): View
    {
        return view('inventory.rolls.create', [
            'activeBatch' => $weighingService->activeBatch(),
        ]);
    }

    public function store(WeighDtyRollRequest $request, BobbinWeighingService $weighingService): RedirectResponse
    {
        $roll = $weighingService->weigh((float) $request->validated('berat_gram'), $request->user());

        return redirect()->route('warehouse.rolls.label', $roll)
            ->with('status', "Bobbin {$roll->nomor_seri} berhasil ditimbang. Stiker siap dicetak.");
    }

    public function edit(DtyRoll $roll): View
    {
        abort_unless($roll->status === RollStatus::ReadyToPack, 403, 'Bobbin yang sudah dipacking tidak dapat diubah.');

        $roll->loadMissing('productionBatch');

        return view('inventory.rolls.edit', compact('roll'));
    }

    public function update(WeighDtyRollRequest $request, DtyRoll $roll): RedirectResponse
    {
        abort_unless($roll->status === RollStatus::ReadyToPack, 403, 'Bobbin yang sudah dipacking tidak dapat diubah.');

        $beratGram = (float) $request->validated('berat_gram');
        $grade = BobbinWeightStandard::gradeFor($beratGram);

        if ($grade === null) {
            return back()->withErrors(['berat_gram' => 'Berat bobbin berada di luar standar grade.'])->withInput();
        }

        $roll->update([
            'berat_gram' => $beratGram,
            'grade' => $grade,
        ]);

        return redirect()->route('warehouse.rolls.index')
            ->with('status', 'Berat bobbin berhasil diperbarui.');
    }

    public function cancel(DtyRoll $roll): RedirectResponse
    {
        $cancellable = $roll->status === RollStatus::ReadyToPack
            || ($roll->status === RollStatus::InStock && $roll->dty_carton_id === null);

        abort_unless($cancellable, 403, 'Hanya bobbin yang belum dipacking dapat dibatalkan.');

        $roll->update(['status' => RollStatus::Cancelled]);

        return back()->with('status', 'Bobbin berhasil dibatalkan dan tetap tersimpan dalam riwayat.');
    }

    public function label(DtyRoll $roll): View
    {
        abort_unless($roll->status !== RollStatus::Cancelled, 404);

        $roll->loadMissing(['carton', 'productionBatch']);

        return view('inventory.rolls.label', [
            'roll' => $roll,
            'qrToken' => $roll->nomor_seri ?? $roll->qr_token,
        ]);
    }
}
