@php
    $menuGroups = [
        'Workspace' => [['admin.dashboard', 'Ringkasan', 'spark', 'admin.dashboard']],
        'Member & poin' => [
            ['admin.members.index', 'Member', 'user', 'admin.members.*'],
            ['admin.points.index', 'Poin member', 'star', 'admin.points.*'],
            ['admin.point-rules.index', 'Poin otomatis', 'spark', 'admin.point-rules.*'],
        ],
        'Layanan & katalog' => [
            ['admin.services.index', 'Layanan', 'gift', 'admin.services.*'],
            ['admin.catalogs.index', 'Katalog undangan', 'heart', 'admin.catalogs.*'],
            ['admin.discounts.index', 'Diskon katalog', 'star', 'admin.discounts.*'],
            ['admin.testimonials.index', 'Testimoni', 'star', 'admin.testimonials.*'],
            ['admin.catalog-categories.index', 'Kategori katalog', 'gift', 'admin.catalog-categories.*'],
        ],
        'Pesanan & pembayaran' => [
            ['admin.tracking.index', 'Tracking', 'calendar', 'admin.tracking.*'],
            ['admin.statuses.index', 'Label & status', 'check', 'admin.statuses.*'],
            ['admin.bills.index', 'Bill', 'receipt', 'admin.bills.*'],
        ],
        'Preferensi' => [['admin.settings.edit', 'Pengaturan', 'info', 'admin.settings.*']],
    ];
@endphp
<aside class="admin-sidebar">
    <x-brand-link size="44" caption="ADMIN SPACE" :destination="route('admin.dashboard')" :settings="$settings" />
    <details class="admin-navigation" open>
        <summary>Menu admin <x-icon name="chevron-down" size="17" /></summary>
        <nav aria-label="Menu admin" class="admin-menu">
            @foreach($menuGroups as $group => $menus)
                <div class="admin-menu-group"><p class="sidebar-kicker">{{ $group }}</p><div class="admin-menu-items">
                    @foreach($menus as [$route, $label, $icon, $pattern])
                        <a href="{{ route($route) }}" class="admin-menu-link {{ request()->routeIs($pattern) ? 'selected' : '' }}" @if(request()->routeIs($pattern)) aria-current="page" @endif><x-icon :name="$icon" size="17" /><span>{{ $label }}</span></a>
                    @endforeach
                </div></div>
            @endforeach
        </nav>
    </details>
    <div class="sidebar-foot"><span class="badge">Administrator</span><p>{{ auth()->user()->name }}</p></div>
</aside>
