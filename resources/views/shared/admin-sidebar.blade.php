@php
    $menuGroups = [
        'Workspace' => [['admin.dashboard', 'Ringkasan', 'spark', 'admin.dashboard']],
        'Member & poin' => [
            ['admin.members.index', 'Member', 'user', 'admin.members.*'],
            ['admin.points.index', 'Poin member', 'star', 'admin.points.*'],
            ['admin.point-rules.index', 'Poin otomatis', 'spark', 'admin.point-rules.*'],
        ],
        'Katalog' => [
            ['admin.catalogs.index', 'Katalog undangan', 'heart', ['admin.catalogs.index', 'admin.catalogs.create', 'admin.catalogs.edit']],
            ['admin.catalogs.archive', 'Arsip katalog', 'gift', 'admin.catalogs.archive'],
            ['admin.discounts.index', 'Diskon katalog', 'star', 'admin.discounts.*'],
            ['admin.testimonials.index', 'Testimoni', 'star', 'admin.testimonials.*'],
            ['admin.catalog-categories.index', 'Kategori katalog', 'gift', 'admin.catalog-categories.*'],
        ],
        'Pesanan & pembayaran' => [
            ['admin.tracking.index', 'Pesanan', 'gift', 'admin.tracking.*'],
            ['admin.statuses.index', 'Label & status', 'check', 'admin.statuses.*'],
        ],
        'Preferensi' => [['admin.settings.edit', 'Pengaturan', 'info', 'admin.settings.*'], ['admin.activity-logs.index', 'Log aktivitas', 'calendar', 'admin.activity-logs.*']],
    ];
    if (! auth()->user()->isSuperAdmin()) {
        unset($menuGroups['Member & poin']);
    } else {
        array_unshift($menuGroups['Preferensi'], ['admin.administrators.index', 'Manajemen admin', 'user', 'admin.administrators.*']);
    }
@endphp
<aside class="admin-sidebar" id="admin-sidebar" aria-label="Navigasi admin">
    <button type="button" class="button button-neutral admin-sidebar-close" data-sidebar-close aria-label="Tutup menu admin"><x-icon name="close" size="20" /></button>
    <x-brand-link size="44" caption="ADMIN SPACE" :destination="route('admin.dashboard')" :settings="$settings" />
    <details class="admin-navigation" open>
        <summary>Menu admin <x-icon name="chevron-down" size="17" /></summary>
        <nav aria-label="Menu admin" class="admin-menu">
            @foreach($menuGroups as $group => $menus)
                <div class="admin-menu-group"><p class="sidebar-kicker">{{ $group }}</p><div class="admin-menu-items">
                    @foreach($menus as [$route, $label, $icon, $pattern])
                        <a href="{{ route($route) }}" class="admin-menu-link {{ request()->routeIs(...(array) $pattern) ? 'selected' : '' }}" @if(request()->routeIs(...(array) $pattern)) aria-current="page" @endif><x-icon :name="$icon" size="17" /><span>{{ $label }}</span></a>
                    @endforeach
                </div></div>
            @endforeach
        </nav>
    </details>
    <div class="sidebar-foot"><span class="badge">{{ auth()->user()->isSuperAdmin() ? 'Superadmin' : 'Admin' }}</span><p>{{ auth()->user()->name }}</p></div>
</aside>
