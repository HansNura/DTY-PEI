@props(['align' => 'right'])

<div x-data="{ open: false }" class="relative inline-flex">
    <div x-on:click="open = ! open" class="inline-flex">
        {{ $trigger }}
    </div>

    <div x-cloak x-show="open" x-on:click.outside="open = false"
         class="absolute z-30 mt-2 w-48 overflow-hidden rounded-xl border border-slate-200 bg-white p-1 shadow-lg {{ $align === 'right' ? 'right-0' : 'left-0' }}">
        {{ $slot }}
    </div>
</div>
