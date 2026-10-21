(() => {
    const header = document.querySelector('.clean-navbar');
    if (!header) return;
    const toggle = header.querySelector('.navbar-toggle');
    const menu = header.querySelector('.main-nav');
    const account = header.querySelector('#site-account-actions');
    const dropdown = header.querySelector('.navbar-dropdown');
    const compact = matchMedia('(max-width: 1024px)');
    let open = false;
    const render = () => {
        toggle.hidden = !compact.matches;
        menu.hidden = account.hidden = compact.matches && !open;
        dropdown.hidden = compact.matches && !open;
        toggle.setAttribute('aria-expanded', String(compact.matches && open));
        toggle.setAttribute('aria-label', open ? window.siteText('Tutup menu navigasi') : window.siteText('Buka menu navigasi'));
        toggle.querySelector('[data-nav-toggle-label]').textContent = open ? window.siteText('Tutup') : 'Menu';
    };
    header.classList.add('navbar-enhanced');
    toggle.addEventListener('click', () => { open = !open; render(); });
    document.addEventListener('click', event => {
        if (open && !header.contains(event.target)) { open = false; render(); }
    });
    menu.addEventListener('click', event => {
        if (event.target.closest('a') && compact.matches) { open = false; render(); }
    });
    header.addEventListener('keydown', event => {
        if (event.key === 'Escape' && compact.matches && open) { open = false; render(); toggle.focus(); }
    });
    compact.addEventListener('change', () => { open = false; render(); });
    render();
})();

