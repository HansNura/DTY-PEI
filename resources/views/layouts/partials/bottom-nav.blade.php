@props(['items'])

<nav class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white/95 backdrop-blur lg:hidden" style="padding-bottom: env(safe-area-inset-bottom)">
    <div class="mx-auto flex max-w-lg items-stretch justify-around">
        @foreach ($items as $item)
            @php $active = request()->routeIs($item['active']); @endphp
            <a href="{{ route($item['route']) }}"
               class="flex min-h-16 flex-1 flex-col items-center justify-center gap-1 px-2 py-2 text-[10px] font-medium transition {{ $active ? 'text-brand-700' : 'text-slate-500 hover:text-slate-700' }}">
                <x-icon :name="$item['icon']" class="size-5" />
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </div>
</nav>
