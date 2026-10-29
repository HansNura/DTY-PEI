<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Http\Requests\Logistics\SaveCustomerRequest;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));
        $status = (string) $request->query('status');

        $customers = Customer::query()
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('nama_pabrik', 'like', "%{$search}%")
                    ->orWhere('kontak_nama', 'like', "%{$search}%");
            }))
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('nama_pabrik')
            ->paginate(15)
            ->withQueryString();

        return view('logistics.customers.index', compact('customers', 'search', 'status'));
    }

    public function create(): View
    {
        return view('logistics.customers.create');
    }

    public function store(SaveCustomerRequest $request): RedirectResponse
    {
        Customer::create($request->validated());

        return redirect()->route('logistics.customers.index')
            ->with('status', 'Data pelanggan berhasil ditambahkan.');
    }

    public function edit(Customer $customer): View
    {
        return view('logistics.customers.edit', compact('customer'));
    }

    public function update(SaveCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $customer->update($request->validated());

        return redirect()->route('logistics.customers.index')
            ->with('status', 'Data pelanggan berhasil diperbarui.');
    }

    public function toggleActive(Customer $customer): RedirectResponse
    {
        $customer->update(['is_active' => ! $customer->is_active]);

        return back()->with('status', $customer->is_active
            ? 'Pelanggan berhasil diaktifkan.'
            : 'Pelanggan berhasil dinonaktifkan.');
    }
}
