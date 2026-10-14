(() => {
    const browser = document.querySelector('.catalog-browser');
    if (!browser) return;
    const results = browser.querySelector('[data-catalog-results]');
    const status = browser.querySelector('[data-catalog-status]');
    const form = browser.querySelector('.catalog-filter');
    const tabs = [...browser.querySelectorAll('.catalog-sort-tabs a')];
    const reset = browser.querySelector('[data-catalog-reset]');
    let request;
    const compact = matchMedia('(max-width: 560px)');
    const filterToggle = form.querySelector('.catalog-filter-toggle');
    const filterOptions = form.querySelector('.catalog-filter-options');
    let filterOpen = false;
    form.classList.add('catalog-filter-enhanced');
    const renderFilters = () => {
        filterToggle.hidden = !compact.matches;
        filterOptions.hidden = compact.matches && !filterOpen;
        filterToggle.setAttribute('aria-expanded', String(compact.matches && filterOpen));
        filterToggle.setAttribute('aria-label', filterOpen ? 'Tutup filter katalog' : 'Buka filter katalog');
        const select = window.jQuery?.(form.elements.category);
        if (filterOptions.hidden && select?.data('select2')) select.select2('close');
    };
    filterToggle.addEventListener('click', () => { filterOpen = !filterOpen; renderFilters(); });
    compact.addEventListener('change', renderFilters);
    renderFilters();

    const syncControls = url => {
        const params = url.searchParams;
        const sort = params.get('sort') || 'featured';
        form.elements.q.value = params.get('q') || '';
        form.elements.category.value = params.get('category') || '';
        form.elements.sort.value = sort;
        form.elements.per_page.value = compact.matches ? '10' : '12';
        window.jQuery?.(form.elements.category).trigger('change.select2');
        tabs.forEach(tab => {
            const link = new URL(tab.href);
            const tabSort = link.searchParams.get('sort');
            link.search = '';
            for (const key of ['q', 'category']) if (params.get(key)) link.searchParams.set(key, params.get(key));
            link.searchParams.set('sort', tabSort);
            tab.href = link.href;
            tab.classList.toggle('selected', tabSort === sort);
            if (tabSort === sort) tab.setAttribute('aria-current', 'page');
            else tab.removeAttribute('aria-current');
        });
        const resetUrl = new URL(form.action);
        resetUrl.search = '';
        resetUrl.searchParams.set('sort', sort);
        reset.href = resetUrl.href;
        reset.hidden = !params.get('q') && !params.get('category');
    };

    const load = async (url, push = true) => {
        url.searchParams.set('per_page', compact.matches ? '10' : '12');
        request?.abort();
        const controller = new AbortController();
        request = controller;
        results.setAttribute('aria-busy', 'true');
        status.textContent = 'Memuat template...';
        status.classList.remove('is-error');
        try {
            const response = await fetch(url, {
                headers: { Accept: 'application/json', 'X-Catalog-Fragment': 'results' },
                signal: controller.signal,
            });
            if (!response.ok) throw new Error('Catalog request failed');
            const data = await response.json();
            if (controller.signal.aborted) return;
            results.innerHTML = data.html;
            browser.querySelector('[data-catalog-count]').textContent = data.total;
            syncControls(url);
            if (push && url.href !== location.href) history.pushState({ catalog: true }, '', url);
            status.textContent = `${data.total} template ditemukan.`;
        } catch (error) {
            if (error.name !== 'AbortError') {
                status.textContent = 'Katalog belum bisa dimuat. Coba pilih tab atau terapkan filter lagi.';
                status.classList.add('is-error');
            }
        } finally {
            if (request === controller) results.setAttribute('aria-busy', 'false');
        }
    };

    browser.addEventListener('click', event => {
        const link = event.target.closest('.catalog-sort-tabs a, [data-catalog-reset], .catalog-pagination a, .catalog-empty a');
        if (!link || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        const url = new URL(link.href);
        if (url.origin !== location.origin || url.pathname !== new URL(form.action).pathname) return;
        event.preventDefault();
        url.hash = 'templates';
        load(url);
    });
    form.addEventListener('submit', event => {
        event.preventDefault();
        const url = new URL(form.action);
        url.search = new URLSearchParams(new FormData(form)).toString();
        load(url);
    });
    window.addEventListener('popstate', () => load(new URL(location.href), false));
    const syncPageSize = () => {
        const size = compact.matches ? '10' : '12';
        form.elements.per_page.value = size;
        if (results.querySelector('[data-page-size]')?.dataset.pageSize !== size || results.getAttribute('aria-busy') === 'true') {
            const url = new URL(location.href);
            url.searchParams.delete('page');
            load(url, false);
        }
    };
    compact.addEventListener('change', syncPageSize);
    syncPageSize();
})();
