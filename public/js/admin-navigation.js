(() => {
    const navigation = document.querySelector('.admin-navigation');
    if (!navigation) return;
    const compact = matchMedia('(max-width: 1024px)');
    const sidebar = document.getElementById('admin-sidebar');
    const toggle = document.querySelector('[data-sidebar-toggle]');
    const backdrop = document.querySelector('.admin-sidebar-backdrop');
    const workspace = document.querySelector('.admin-workspace');
    const setOpen = (open, restoreFocus = true) => {
        const shown = compact.matches && open;
        document.body.classList.toggle('admin-sidebar-open', shown);
        toggle.setAttribute('aria-expanded', String(shown));
        toggle.setAttribute('aria-label', shown ? 'Tutup menu admin' : 'Buka menu admin');
        backdrop.hidden = !shown;
        sidebar.inert = compact.matches && !shown;
        workspace.inert = shown;
        if (shown) sidebar.querySelector('[data-sidebar-close]').focus();
        else if (restoreFocus && compact.matches) toggle.focus();
    };
    const adapt = () => { navigation.open = true; setOpen(false, false); };
    toggle.addEventListener('click', () => setOpen(!document.body.classList.contains('admin-sidebar-open')));
    document.querySelectorAll('[data-sidebar-close]').forEach(button => button.addEventListener('click', () => setOpen(false)));
    document.addEventListener('keydown', event => {
        if (!document.body.classList.contains('admin-sidebar-open')) return;
        if (event.key === 'Escape') { event.preventDefault(); setOpen(false); }
        if (event.key === 'Tab') {
            const items = [...sidebar.querySelectorAll('a[href], button:not([disabled])')].filter(item => item.getClientRects().length);
            const first = items[0], last = items[items.length - 1];
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
        }
    });
    adapt();
    compact.addEventListener('change', adapt);
    document.querySelectorAll('.portal-table-wrap').forEach(wrapper => {
        wrapper.tabIndex = 0;
        wrapper.setAttribute('role', 'region');
        wrapper.setAttribute('aria-label', 'Tabel data. Geser ke samping untuk melihat semua kolom.');
    });
})();
