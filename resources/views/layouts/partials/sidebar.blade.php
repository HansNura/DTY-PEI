@props(['groups', 'user'])

<aside class="hidden w-64 shrink-0 border-r border-slate-200/80 bg-white lg:sticky lg:top-0 lg:z-20 lg:flex lg:h-screen lg:flex-col lg:overflow-hidden">
    <div class="flex h-16 shrink-0 items-center border-b border-slate-100 px-5">
        <x-app-logo />
    </div>

    <div class="flex-1 overflow-y-auto px-3 py-4">
        @foreach ($groups as $group)
            <p class="px-2 pb-2 pt-4 text-[10px] font-semibold uppercase tracking-wider text-slate-400">{{ $group['label'] }}</p>
            <ul class="space-y-1">
                @foreach ($group['items'] as $item)
                    @php $active = request()->routeIs($item['active']); @endphp
                    <li>
                        <a href="{{ route($item['route']) }}"
                           class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition {{ $active ? 'bg-brand-50 font-medium text-brand-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                            <x-icon :name="$item['icon']" class="size-5 {{ $active ? 'text-brand-600' : 'text-slate-400 group-hover:text-slate-600' }}" />
                            <span class="truncate">{{ $item['label'] }}</span>
                            @if ($active)
                                <span class="ml-auto size-1.5 rounded-full bg-brand-500"></span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        @endforeach
    </div>

    <div class="border-t border-slate-100 p-3">
        <div class="flex items-center gap-3 rounded-xl bg-slate-50 px-3 py-2.5">
            <x-avatar :name="$user->nama" size="size-8" />
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium text-slate-900">{{ $user->nama }}</p>
                <p class="truncate text-xs text-slate-500">{{ $user->role->label() }}</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="rounded-lg p-1.5 text-slate-400 transition hover:bg-white hover:text-rose-600" aria-label="Keluar">
                    <x-icon name="logout" class="size-4" />
                </button>
            </form>
        </div>
    </div>
</aside>
