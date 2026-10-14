(() => {
    const navigation = document.querySelector('.admin-navigation');
    if (!navigation) return;
    const compact = matchMedia('(max-width: 800px)');
    const adapt = () => { navigation.open = !compact.matches; };
    adapt();
    compact.addEventListener('change', adapt);
    document.querySelectorAll('.portal-table-wrap').forEach(wrapper => {
        wrapper.tabIndex = 0;
        wrapper.setAttribute('role', 'region');
        wrapper.setAttribute('aria-label', 'Tabel data. Geser ke samping untuk melihat semua kolom.');
    });
})();
