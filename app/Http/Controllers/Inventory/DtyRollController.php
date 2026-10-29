<?php

namespace App\Http\Controllers\Inventory;

use App\Enums\RollStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\SaveDtyRollRequest;
use App\Models\DtyRoll;
use App\Services\QrCodeService;
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
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('kode_rol', 'like', "%{$search}%")
                    ->orWhere('nomor_lot', 'like', "%{$search}%");
            }))
            ->when(RollStatus::tryFrom($status) !== null, fn ($query) => $query->where('status', $status))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('inventory.rolls.index', [
            'rolls' => $rolls,
            'statuses' => RollStatus::cases(),
            'search' => $search,
            'status' => $status,
        ]);
    }

    public function create(): View
    {
        return view('inventory.rolls.create');
    }

    public function store(SaveDtyRollRequest $request, QrCodeService $qrCodeService): RedirectResponse
    {
        $roll = DtyRoll::create([
            ...$request->validated(),
            'kode_rol' => $qrCodeService->generateRollCode(),
            'qr_token' => $qrCodeService->generateToken(),
            'status' => RollStatus::InStock,
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('warehouse.rolls.label', $roll)
            ->with('status', 'Data gulungan berhasil disimpan. Label QR siap dicetak.');
    }

    public function edit(DtyRoll $roll): View
    {
        abort_unless($roll->status === RollStatus::InStock, 403, 'Gulungan yang sudah dialokasikan tidak dapat diubah.');

        return view('inventory.rolls.edit', compact('roll'));
    }

    public function update(SaveDtyRollRequest $request, DtyRoll $roll): RedirectResponse
    {
        abort_unless($roll->status === RollStatus::InStock, 403, 'Gulungan yang sudah dialokasikan tidak dapat diubah.');

        $roll->update($request->validated());

        return redirect()->route('warehouse.rolls.index')
            ->with('status', 'Data gulungan berhasil diperbarui.');
    }

    public function cancel(DtyRoll $roll): RedirectResponse
    {
        abort_unless($roll->status === RollStatus::InStock, 403, 'Hanya gulungan tersedia yang dapat dibatalkan.');

        $roll->update(['status' => RollStatus::Cancelled]);

        return back()->with('status', 'Gulungan berhasil dibatalkan dan tetap tersimpan dalam riwayat.');
    }

    public function label(DtyRoll $roll): View
    {
        abort_unless($roll->status !== RollStatus::Cancelled, 404);

        return view('inventory.rolls.label', [
            'roll' => $roll,
            'qrToken' => $roll->qr_token,
        ]);
    }
}
