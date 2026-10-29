<div class="grid gap-5 sm:grid-cols-2">
    <div>
        <label for="denier" class="block text-sm font-medium text-slate-700">Denier</label>
        <input id="denier" name="denier" type="number" min="1" step="1" value="{{ old('denier', $roll->denier ?? '') }}" required
               class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
    </div>
    <div>
        <label for="filamen" class="block text-sm font-medium text-slate-700">Jumlah filamen</label>
        <input id="filamen" name="filamen" type="number" min="1" step="1" value="{{ old('filamen', $roll->filamen ?? '') }}" required
               class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
    </div>
    <div>
        <label for="berat_gram" class="block text-sm font-medium text-slate-700">Berat bersih (gram)</label>
        <input id="berat_gram" name="berat_gram" type="number" min="0.01" step="0.01" value="{{ old('berat_gram', $roll->berat_gram ?? '') }}" required
               class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
    </div>
    <div>
        <label for="nomor_lot" class="block text-sm font-medium text-slate-700">Nomor lot</label>
        <input id="nomor_lot" name="nomor_lot" type="text" maxlength="50" value="{{ old('nomor_lot', $roll->nomor_lot ?? '') }}" required
               class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
    </div>
</div>
