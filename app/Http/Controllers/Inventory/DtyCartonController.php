<?php

namespace App\Http\Controllers\Inventory;

use App\Enums\RollStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\SaveDtyCartonRequest;
use App\Models\DtyCarton;
use App\Models\DtyRoll;
use App\Services\QrCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DtyCartonController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));

        $cartons = DtyCarton::query()
            ->with(['rolls', 'creator'])
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('nomor_hu', 'like', "%{$search}%")
                    ->orWhere('nomor_merge', 'like', "%{$search}%")
                    ->orWhere('pallet_number', 'like', "%{$search}%")
                    ->orWhere('grade', 'like', "%{$search}%");
            }))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('inventory.cartons.index', [
            'cartons' => $cartons,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('inventory.cartons.create');
    }

    public function store(SaveDtyCartonRequest $request, QrCodeService $qrCodeService): RedirectResponse
    {
        $carton = DB::transaction(function () use ($request, $qrCodeService) {
            $carton = DtyCarton::create([
                ...$request->validated(),
                'qr_token' => $qrCodeService->generateToken(),
                'created_by' => $request->user()->id,
            ]);

            $this->createRolls($carton, $qrCodeService);

            return $carton;
        });

        return redirect()->route('warehouse.cartons.label', $carton)
            ->with('status', "Kardus {$carton->nomor_hu} berhasil disimpan dengan {$carton->unit_bobbins} bobbin. Label siap dicetak.");
    }

    public function edit(DtyCarton $carton): View
    {
        $this->ensureEditable($carton);

        return view('inventory.cartons.edit', compact('carton'));
    }

    public function update(SaveDtyCartonRequest $request, DtyCarton $carton): RedirectResponse
    {
        $this->ensureEditable($carton);

        $data = $request->validated();

        if ((int) $data['unit_bobbins'] !== $carton->rolls()->count()) {
            throw ValidationException::withMessages([
                'unit_bobbins' => 'Jumlah bobbin tidak dapat diubah setelah kardus dibuat.',
            ]);
        }

        DB::transaction(function () use ($carton, $data) {
            $carton->update($data);
            $carton->refresh();

            $carton->rolls()->update([
                'denier' => $data['denier'],
                'filamen' => $data['filamen'],
                'nomor_lot' => $data['nomor_merge'],
                'berat_gram' => $carton->net_weight_per_bobbin_gram,
            ]);
        });

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

    private function createRolls(DtyCarton $carton, QrCodeService $qrCodeService): void
    {
        $beratPerBobbin = $carton->net_weight_per_bobbin_gram;

        for ($index = 0; $index < $carton->unit_bobbins; $index++) {
            DtyRoll::create([
                'dty_carton_id' => $carton->id,
                'kode_rol' => $qrCodeService->generateRollCode(),
                'nomor_seri' => $qrCodeService->generateRollSerial($carton->production_date),
                'denier' => $carton->denier,
                'filamen' => $carton->filamen,
                'berat_gram' => $beratPerBobbin,
                'nomor_lot' => $carton->nomor_merge,
                'qr_token' => $qrCodeService->generateToken(),
                'status' => RollStatus::InStock,
                'created_by' => $carton->created_by,
            ]);
        }
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
