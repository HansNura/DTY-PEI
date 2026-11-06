<?php

namespace App\Http\Controllers\Logistics;

use App\Enums\DeliveryOrderStatus;
use App\Enums\RollStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Logistics\SaveDeliveryOrderRequest;
use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\DtyRoll;
use App\Models\Fleet;
use App\Models\User;
use App\Services\DeliveryOrderService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class DeliveryOrderController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));
        $status = (string) $request->query('status');

        $orders = DeliveryOrder::query()
            ->with(['customer', 'fleet', 'driver', 'creator', 'items.dtyRoll'])
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('nomor_do', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($query) => $query->where('nama_pabrik', 'like', "%{$search}%"))
                    ->orWhereHas('fleet', fn ($query) => $query->where('nomor_polisi', 'like', "%{$search}%"))
                    ->orWhereHas('driver', fn ($query) => $query->where('nama', 'like', "%{$search}%"));
            }))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $statuses = DeliveryOrderStatus::cases();

        return view('logistics.orders.index', compact('orders', 'search', 'status', 'statuses'));
    }

    public function create(): View
    {
        $customers = Customer::where('is_active', true)->orderBy('nama_pabrik')->get();
        $fleets = Fleet::where('is_active', true)->with('driver')->orderBy('nomor_polisi')->get();
        $drivers = User::where('role', UserRole::Driver)->where('is_active', true)->orderBy('nama')->get();
        $availableRolls = DtyRoll::with('carton')
            ->where('status', RollStatus::InStock)
            ->orderBy('nomor_lot')
            ->orderBy('kode_rol')
            ->get();

        $boxGroups = $availableRolls
            ->groupBy(fn (DtyRoll $roll): string => $roll->carton?->nomor_merge ?: 'TANPA-KARDUS')
            ->map(function ($rolls, string $mergeKey): array {
                $cartons = $rolls
                    ->groupBy(fn (DtyRoll $roll): string => $roll->carton
                        ? 'carton-'.$roll->carton->id
                        : 'roll-'.$roll->id)
                    ->map(function ($cartonRolls): array {
                        $carton = $cartonRolls->first()->carton;
                        $availableCount = $cartonRolls->count();
                        $expectedCount = $carton?->unit_bobbins ?? $availableCount;

                        return [
                            'carton' => $carton,
                            'rolls' => $cartonRolls->sortBy('nomor_seri')->values(),
                            'roll_ids' => $cartonRolls->pluck('id')->map(fn ($id): int => (int) $id)->values()->all(),
                            'net_kg' => round($cartonRolls->sum(fn (DtyRoll $roll): float => (float) $roll->berat_gram) / 1000, 2),
                            'available_count' => $availableCount,
                            'expected_count' => $expectedCount,
                            'selectable' => $carton !== null && $availableCount === $expectedCount && $expectedCount > 0,
                        ];
                    })
                    ->sortBy(fn (array $group): string => $group['carton']?->nomor_hu ?? 'ZZZ')
                    ->values();

                $selectableRollIds = $cartons
                    ->filter(fn (array $carton): bool => $carton['selectable'])
                    ->flatMap(fn (array $carton): array => $carton['roll_ids'])
                    ->values();

                return [
                    'merge' => $mergeKey === 'TANPA-KARDUS' ? null : $mergeKey,
                    'label' => $mergeKey === 'TANPA-KARDUS' ? 'Tanpa kardus' : 'Merge '.$mergeKey,
                    'cartons' => $cartons,
                    'selectable_roll_ids' => $selectableRollIds->all(),
                    'selectable' => $selectableRollIds->isNotEmpty(),
                    'bobbin_count' => $rolls->count(),
                    'carton_count' => $cartons->count(),
                    'net_kg' => round($rolls->sum(fn (DtyRoll $roll): float => (float) $roll->berat_gram) / 1000, 2),
                ];
            })
            ->sortBy(fn (array $group): string => $group['merge'] ?? 'ZZZ')
            ->values();

        $selectableRollIds = $boxGroups
            ->flatMap(fn (array $group): array => $group['selectable_roll_ids'])
            ->unique()
            ->values();

        $rollWeightsKg = $availableRolls->mapWithKeys(
            fn (DtyRoll $roll): array => [$roll->id => round((float) $roll->berat_gram / 1000, 3)]
        );
        $rollCartonKeys = $availableRolls->mapWithKeys(
            fn (DtyRoll $roll): array => [
                $roll->id => $roll->carton ? 'carton-'.$roll->carton->id : 'roll-'.$roll->id,
            ]
        );
        $cartonMeta = $availableRolls
            ->filter(fn (DtyRoll $roll): bool => $roll->carton !== null)
            ->groupBy(fn (DtyRoll $roll): int => $roll->carton->id)
            ->map(function ($cartonRolls): array {
                $carton = $cartonRolls->first()->carton;

                return [
                    'key' => 'carton-'.$carton->id,
                    'hu' => $carton->nomor_hu,
                    'merge' => $carton->nomor_merge,
                    'net_kg' => round($cartonRolls->sum(fn (DtyRoll $roll): float => (float) $roll->berat_gram) / 1000, 2),
                    'bobbin_count' => $cartonRolls->count(),
                ];
            })
            ->keyBy('key');

        return view('logistics.orders.create', [
            'customers' => $customers,
            'fleets' => $fleets,
            'drivers' => $drivers,
            'fleetDrivers' => $fleets->mapWithKeys(fn ($fleet): array => [$fleet->id => $fleet->driver_id]),
            'availableRolls' => $availableRolls,
            'boxGroups' => $boxGroups,
            'allRollIds' => $selectableRollIds,
            'selectableRollCount' => $selectableRollIds->count(),
            'rollWeightsKg' => $rollWeightsKg,
            'rollCartonKeys' => $rollCartonKeys,
            'cartonMeta' => $cartonMeta,
        ]);
    }

    public function store(SaveDeliveryOrderRequest $request, DeliveryOrderService $service): RedirectResponse
    {
        $validated = $request->validated();
        $rollIds = array_map('intval', $validated['rolls']);

        $order = $service->createDeliveryOrder($validated, $rollIds, $request->user());

        return redirect()->route('logistics.orders.show', $order)
            ->with('status', "Surat Jalan {$order->nomor_do} berhasil diterbitkan dengan ".count($rollIds).' rol benang.');
    }

    public function show(DeliveryOrder $order): View
    {
        $order->load([
            'customer',
            'fleet',
            'driver',
            'creator',
            'items.dtyRoll.carton',
            'loadScans.scanner',
            'loadScans.dtyCarton',
            'loadScans.dtyRoll',
            'proofOfDelivery',
            'trackingEvents.recorder',
        ]);

        return view('logistics.orders.show', compact('order'));
    }

    public function pdf(DeliveryOrder $order): Response
    {
        $order->load(['customer', 'fleet', 'driver', 'items.dtyRoll.carton', 'proofOfDelivery']);
        $signatureDataUri = $order->proofOfDelivery
            ? 'data:image/png;base64,'.base64_encode(Storage::disk('local')->get($order->proofOfDelivery->signature_path))
            : null;

        return Pdf::loadView('logistics.orders.pdf', compact('order', 'signatureDataUri'))
            ->setPaper('a4', 'portrait')
            ->download($order->nomor_do.'.pdf');
    }

    public function print(DeliveryOrder $order): View
    {
        $order->load(['customer', 'fleet', 'driver', 'items.dtyRoll.carton']);

        return view('logistics.orders.print', compact('order'));
    }

    public function cancel(DeliveryOrder $order, DeliveryOrderService $service): RedirectResponse
    {
        $service->cancel($order);

        return redirect()->route('logistics.orders.show', $order)
            ->with('status', "Surat Jalan {$order->nomor_do} telah dibatalkan dan rol dikembalikan ke stok gudang.");
    }
}
