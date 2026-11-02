@props(['label' => null, 'description' => null, 'name' => null, 'checked' => false, 'value' => '1'])

<label class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50/70 px-4 py-3">
    <input type="checkbox" name="{{ $name }}" value="{{ $value }}" @checked($checked)
           {{ $attributes->merge(['class' => 'mt-0.5 size-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500']) }} />
    <span>
        @if ($label)
            <span class="block text-sm font-medium text-slate-800">{{ $label }}</span>
        @endif
        @if ($description)
            <span class="block text-xs text-slate-500">{{ $description }}</span>
        @endif
        {{ $slot }}
    </span>
</label>
