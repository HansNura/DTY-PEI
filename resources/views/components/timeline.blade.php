@props([])

<ol {{ $attributes->merge(['class' => 'relative space-y-5 border-l border-slate-200 pl-5']) }}>
    {{ $slot }}
</ol>
