<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\SaveProductionBatchRequest;
use App\Models\ProductionBatch;
use App\Services\ProductionBatchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProductionBatchController extends Controller
{
    public function index(Request $request, ProductionBatchService $batchService): View
    {
        $search = trim((string) $request->query('q'));

        $batches = ProductionBatch::query()
            ->withCount('rolls')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('nomor_merge', 'like', "%{$search}%")
                    ->orWhere('denier', 'like', "%{$search}%")
                    ->orWhere('filamen', 'like', "%{$search}%");
            }))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('inventory.batches.index', [
            'batches' => $batches,
            'activeBatch' => $batchService->active(),
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('inventory.batches.create');
    }

    public function store(
        SaveProductionBatchRequest $request,
        ProductionBatchService $batchService,
    ): RedirectResponse {
        $batch = ProductionBatch::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        if (! ProductionBatch::query()->active()->exists()) {
            $batchService->activate($batch);
        }

        return redirect()->route('warehouse.batches.index')
            ->with('status', "Batch {$batch->nomor_merge} berhasil ditambahkan.");
    }

    public function edit(ProductionBatch $batch): View
    {
        return view('inventory.batches.edit', compact('batch'));
    }

    public function update(SaveProductionBatchRequest $request, ProductionBatch $batch): RedirectResponse
    {
        if ($batch->rolls()->exists()) {
            throw ValidationException::withMessages([
                'nomor_merge' => 'Batch yang sudah memiliki bobbin tidak dapat diubah agar label bobbin tetap konsisten.',
            ]);
        }

        $batch->update($request->validated());

        return redirect()->route('warehouse.batches.index')
            ->with('status', "Batch {$batch->nomor_merge} berhasil diperbarui.");
    }

    public function activate(ProductionBatch $batch, ProductionBatchService $batchService): RedirectResponse
    {
        $batchService->activate($batch);

        return back()->with('status', "Batch {$batch->nomor_merge} sekarang aktif untuk penimbangan bobbin.");
    }
}
