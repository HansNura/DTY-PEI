@props([])

<div {{ $attributes->merge(['class' => 'grid gap-4 sm:grid-cols-2 xl:grid-cols-4']) }}>
    {{ $slot }}
</div>
