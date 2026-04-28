@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-1 pt-1 border-b-2 border-[#FFA500] text-sm font-medium leading-5 text-[#020f40] dark:text-white focus:outline-none focus:border-[#FFA500] transition duration-150 ease-in-out'
            : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-slate-600 dark:text-gray-400 hover:text-[#020f40] dark:hover:text-white hover:border-[#FFA500]/70 focus:outline-none focus:text-[#020f40] dark:focus:text-white focus:border-[#FFA500]/70 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
