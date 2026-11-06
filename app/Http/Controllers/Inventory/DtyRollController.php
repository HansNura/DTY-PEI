<?php

namespace App\Http\Controllers\Inventory;

use App\Enums\RollStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\WeighDtyRollRequest;
use App\Models\DtyRoll;
use App\Services\BobbinWeighingService;
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
            'overweight' => (int) ($statusCounts[RollStatus::Overweight->value] ?? 0),
            'waste' => (int) ($statusCounts[RollStatus::Waste->value] ?? 0),
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
        $activeBatch = $weighingService->activeBatch();
        $preview = $weighingService->preview($activeBatch);

        return view('inventory.rolls.create', [
            'activeBatch' => $activeBatch,
            'previewSerial' => $preview['serial'],
            'previewKodeRol' => $preview['code'],
        ]);
    }

    public function store(WeighDtyRollRequest $request, BobbinWeighingService $weighingService): RedirectResponse
    {
        $roll = $weighingService->weigh(
            (float) $request->validated('berat_gram'),
            $request->user(),
            $request->validated('nomor_seri'),
            $request->validated('kode_rol'),
        );

        if ($roll->status === RollStatus::Waste) {
            return redirect()->route('warehouse.rolls.index')
                ->with('status', "Bobbin {$roll->nomor_seri} dicatat sebagai WASTE / SCRAP. Label standar tidak dicetak; kumpulkan ke keranjang limbah.");
        }

        $message = $roll->status === RollStatus::Overweight
            ? "Bobbin {$roll->nomor_seri} masuk OVERWEIGHT / HOLD. Label khusus grade OW dapat dicetak; jangan dipacking reguler."
            : "Bobbin {$roll->nomor_seri} berhasil ditimbang. Stiker siap dicetak.";

        return redirect()->route('warehouse.rolls.label', $roll)->with('status', $message);
    }

    public function edit(DtyRoll $roll): View
    {
        abort_unless(
            in_array($roll->status, [RollStatus::ReadyToPack, RollStatus::Overweight, RollStatus::Waste], true),
            403,
            'Bobbin yang sudah dipacking tidak dapat diubah.',
        );

        $roll->loadMissing('productionBatch');

        return view('inventory.rolls.edit', compact('roll'));
    }

    public function update(
        WeighDtyRollRequest $request,
        DtyRoll $roll,
        BobbinWeighingService $weighingService,
    ): RedirectResponse {
        abort_unless(
            in_array($roll->status, [RollStatus::ReadyToPack, RollStatus::Overweight, RollStatus::Waste], true),
            403,
            'Bobbin yang sudah dipacking tidak dapat diubah.',
        );

        $roll = $weighingService->reweigh($roll, (float) $request->validated('berat_gram'));

        $message = match ($roll->status) {
            RollStatus::Waste => 'Bobbin diperbarui sebagai WASTE / SCRAP. Label standar tidak dicetak.',
            RollStatus::Overweight => 'Bobbin diperbarui sebagai OVERWEIGHT / HOLD. Jangan dipacking reguler.',
            default => 'Berat bobbin berhasil diperbarui.',
        };

        return redirect()->route('warehouse.rolls.index')->with('status', $message);
    }

    public function cancel(DtyRoll $roll): RedirectResponse
    {
        $cancellable = in_array($roll->status, [
            RollStatus::ReadyToPack,
            RollStatus::Overweight,
            RollStatus::Waste,
        ], true)
            || ($roll->status === RollStatus::InStock && $roll->dty_carton_id === null);

        abort_unless($cancellable, 403, 'Hanya bobbin yang belum dipacking dapat dibatalkan.');

        $roll->update(['status' => RollStatus::Cancelled]);

        return back()->with('status', 'Bobbin berhasil dibatalkan dan tetap tersimpan dalam riwayat.');
    }

    public function label(DtyRoll $roll): View
    {
        abort_if($roll->status === RollStatus::Waste, 403, 'Bobbin Waste / Scrap tidak memiliki label standar.');
        abort_unless($roll->status !== RollStatus::Cancelled, 404);

        $roll->loadMissing(['carton', 'productionBatch']);

        return view('inventory.rolls.label', [
            'roll' => $roll,
            'qrToken' => $roll->nomor_seri ?? $roll->qr_token,
        ]);
    }
}
