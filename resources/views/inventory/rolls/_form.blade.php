<div class="grid gap-5 sm:grid-cols-2">
    <x-field label="Denier" name="denier" required>
        <x-input id="denier" name="denier" type="number" min="1" step="1" value="{{ old('denier', $roll->denier ?? '') }}" required />
    </x-field>

    <x-field label="Jumlah filamen" name="filamen" required>
        <x-input id="filamen" name="filamen" type="number" min="1" step="1" value="{{ old('filamen', $roll->filamen ?? '') }}" required />
    </x-field>

    <x-field label="Berat bersih (gram)" name="berat_gram" required>
        <x-input id="berat_gram" name="berat_gram" type="number" min="0.01" step="0.01" value="{{ old('berat_gram', $roll->berat_gram ?? '') }}" required />
    </x-field>

    <x-field label="Nomor lot" name="nomor_lot" required>
        <x-input id="nomor_lot" name="nomor_lot" type="text" maxlength="50" value="{{ old('nomor_lot', $roll->nomor_lot ?? '') }}" required />
    </x-field>
</div>
