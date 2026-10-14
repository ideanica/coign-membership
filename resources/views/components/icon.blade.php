@props(['name', 'size' => 20])
<svg {{ $attributes->merge(['class' => 'icon']) }} width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($name)
        @case('star') <path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-3-5.6 3 1.1-6.2L3 9.6l6.2-.9Z"/> @break
        @case('spark') <path d="m12 2 2.2 6.8L21 7l-4.2 5.5L21 18l-6.8-1.8L12 23l-2.2-6.8L3 18l4.2-5.5L3 7l6.8 1.8Z"/> @break
        @case('arrow-up-right') <path d="M7 17 17 7M7 7h10v10"/> @break
        @case('arrow-down-left') <path d="M17 7 7 17M7 7v10h10"/> @break
        @case('arrow-right') <path d="M4 12h16m-6-6 6 6-6 6"/> @break
        @case('arrow-up') <path d="M12 19V5m-6 6 6-6 6 6"/> @break
        @case('arrow-down') <path d="M12 5v14m-6-6 6 6 6-6"/> @break
        @case('search') <circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 4.5 4.5"/> @break
        @case('filter') <path d="M4 5h16l-6 7v6l-4 2v-8L4 5Z"/> @break
        @case('calendar') <rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 11h18m-12 5h2"/> @break
        @case('chevron-down') <path d="m6 9 6 6 6-6"/> @break
        @case('chevron-left') <path d="m15 6-6 6 6 6"/> @break
        @case('chevron-right') <path d="m9 6 6 6-6 6"/> @break
        @case('download') <path d="M12 3v12m-5-5 5 5 5-5M5 15v5h14v-5"/> @break
        @case('receipt') <path d="m5 3 2 1 2-1 3 1 3-1 2 1 2-1v18l-2-1-2 1-3-1-3 1-2-1-2 1ZM9 8h6M9 12h6M9 16h3"/> @break
        @case('gift') <rect x="3" y="8" width="18" height="4" rx="1"/><path d="M5 12v9h14v-9M12 8v13"/><path d="M12 8H8.5a2.5 2.5 0 1 1 2.4-3.2L12 8Zm0 0h3.5a2.5 2.5 0 1 0-2.4-3.2L12 8Z"/> @break
        @case('mail') <rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 6 9 7 9-7"/> @break
        @case('check') <path d="m5 12 4 4L19 6"/> @break
        @case('info') <circle cx="12" cy="12" r="9"/><path d="M12 11v5m0-9h.01"/> @break
        @case('close') <path d="m6 6 12 12M6 18 18 6"/> @break
        @case('user') <circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/> @break
        @case('code') <path d="m8 6-6 6 6 6m8-12 6 6-6 6M14 3l-4 18"/> @break
        @case('terminal') <rect x="2" y="4" width="20" height="16" rx="2"/><path d="m6 8 4 4-4 4m7 0h5"/> @break
        @case('heart') <path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0l-1 1-1-1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8Z"/> @break
    @endswitch
</svg>
