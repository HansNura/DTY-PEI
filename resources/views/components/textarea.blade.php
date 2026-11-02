@props(['invalid' => false])

<textarea {{ $attributes->merge([
    'class' => 'block w-full rounded-xl border bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 transition focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 focus:outline-none '
        .($invalid ? 'border-rose-300' : 'border-slate-200'),
]) }}>{{ $slot }}</textarea>
