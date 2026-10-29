<?php

namespace App\Http\Controllers\Logistics;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Logistics\SaveFleetRequest;
use App\Models\Fleet;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FleetController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));
        $status = (string) $request->query('status');

        $fleets = Fleet::query()
            ->with('driver')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('nomor_polisi', 'like', "%{$search}%")
                    ->orWhere('jenis_kendaraan', 'like', "%{$search}%")
                    ->orWhereHas('driver', fn ($query) => $query->where('nama', 'like', "%{$search}%"));
            }))
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('nomor_polisi')
            ->paginate(15)
            ->withQueryString();

        return view('logistics.fleets.index', compact('fleets', 'search', 'status'));
    }

    public function create(): View
    {
        return view('logistics.fleets.create', [
            'drivers' => $this->activeDrivers(),
        ]);
    }

    public function store(SaveFleetRequest $request): RedirectResponse
    {
        Fleet::create($request->validated());

        return redirect()->route('logistics.fleets.index')
            ->with('status', 'Data armada berhasil ditambahkan.');
    }

    public function edit(Fleet $fleet): View
    {
        return view('logistics.fleets.edit', [
            'fleet' => $fleet,
            'drivers' => $this->activeDrivers(),
        ]);
    }

    public function update(SaveFleetRequest $request, Fleet $fleet): RedirectResponse
    {
        $fleet->update($request->validated());

        return redirect()->route('logistics.fleets.index')
            ->with('status', 'Data armada berhasil diperbarui.');
    }

    public function toggleActive(Fleet $fleet): RedirectResponse
    {
        $fleet->update(['is_active' => ! $fleet->is_active]);

        return back()->with('status', $fleet->is_active
            ? 'Armada berhasil diaktifkan.'
            : 'Armada berhasil dinonaktifkan.');
    }

    /**
     * @return Collection<int, User>
     */
    private function activeDrivers(): Collection
    {
        return User::query()
            ->where('role', UserRole::Driver->value)
            ->where('is_active', true)
            ->orderBy('nama')
            ->get(['id', 'nama']);
    }
}
