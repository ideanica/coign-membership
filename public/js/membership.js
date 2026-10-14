(() => {
    'use strict';

    const dataElement = document.getElementById('membership-data');
    if (!dataElement) return;

    const data = JSON.parse(dataElement.textContent);
    const pageSize = 6;
    const state = { tab: 'transactions', query: '', period: 'all', sort: 'newest', page: 1 };
    const tabs = [...document.querySelectorAll('[data-tab]')];
    const rowsElement = document.getElementById('history-rows');
    const columnsElement = document.getElementById('history-columns');
    const panel = document.getElementById('history-panel');
    const countElement = document.getElementById('history-count');
    const emptyElement = document.getElementById('history-empty');
    const table = document.querySelector('.history-table');
    const pageNumbers = document.getElementById('page-numbers');
    const previous = document.getElementById('page-previous');
    const next = document.getElementById('page-next');
    const search = document.getElementById('history-search');
    const period = document.getElementById('history-period');
    const sort = document.getElementById('history-sort');
    const detailDialog = document.getElementById('detail-dialog');
    const guideDialog = document.getElementById('guide-dialog');
    const number = new Intl.NumberFormat('id-ID');
    const dateFormatter = new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric', timeZone: 'UTC' });
    const labels = { transactions: 'transaksi', redemptions: 'penggunaan poin', earnings: 'pendapatan poin' };
    let toastTimer;

    const escape = (value) => String(value).replace(/[&<>"']/g, (character) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[character]);
    const formatDate = (value) => dateFormatter.format(new Date(`${value}T00:00:00Z`));
    const rupiah = (value) => `Rp${number.format(value)}`;
    const paths = {
        receipt: '<path d="m5 3 2 1 2-1 3 1 3-1 2 1 2-1v18l-2-1-2 1-3-1-3 1-2-1-2 1ZM9 8h6M9 12h6M9 16h3"/>',
        gift: '<rect x="3" y="8" width="18" height="4" rx="1"/><path d="M5 12v9h14v-9M12 8v13M12 8H8.5a2.5 2.5 0 1 1 2.4-3.2L12 8Zm0 0h3.5a2.5 2.5 0 1 0-2.4-3.2L12 8Z"/>',
        star: '<path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-3-5.6 3 1.1-6.2L3 9.6l6.2-.9Z"/>',
        arrow: '<path d="M7 17 17 7M7 7h10v10"/>',
        check: '<path d="m5 12 4 4L19 6"/>',
    };
    const icon = (name, size = 19) => `<svg class="icon" width="${size}" height="${size}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${paths[name]}</svg>`;

    function getSource() {
        return data[state.tab];
    }

    function getFilteredRows() {
        const query = state.query.trim().toLocaleLowerCase('id-ID');
        return getSource().filter((row) => {
            const matchesPeriod = state.period === 'all' || row.date.startsWith(state.period);
            const searchable = `${row.title} ${row.id} ${row.date} ${formatDate(row.date)} ${row.category} ${row.payment || ''}`.toLocaleLowerCase('id-ID');
            return matchesPeriod && (!query || searchable.includes(query));
        }).sort((a, b) => {
            const order = a.date.localeCompare(b.date) || a.id.localeCompare(b.id);
            return state.sort === 'newest' ? -order : order;
        });
    }

    function renderRow(row) {
        const redemption = state.tab === 'redemptions';
        const earning = state.tab === 'earnings';
        const rowIcon = redemption ? 'gift' : earning ? 'star' : 'receipt';
        const subtitle = earning ? (row.transaction_reference ? `Poin dari ${row.transaction_reference}` : row.id) : row.id;
        const amountColumn = redemption ? '' : `<td class="${earning ? '' : 'numeric amount-cell'}">${earning ? escape(row.category) : rupiah(row.amount)}</td>`;
        const points = `${redemption ? '−' : '+'}${number.format(row.points)}`;
        const status = redemption ? 'Ditukarkan' : earning ? 'Diterima' : 'Selesai';

        return `<tr>
            <td><div class="activity-cell"><span class="row-icon category-${escape(row.category.toLowerCase())}">${icon(rowIcon)}</span><div><strong>${escape(row.title)}</strong><span>${escape(subtitle)}</span></div></div></td>
            <td><time datetime="${escape(row.date)}">${formatDate(row.date)}</time></td>
            ${amountColumn}
            <td class="numeric"><span class="point-change ${redemption ? 'negative' : 'positive'}">${points}</span></td>
            <td><span class="badge success-badge">${icon('check', 12)} ${status}</span></td>
            <td><button class="detail-button" type="button" data-detail="${escape(row.id)}" aria-label="Lihat detail ${escape(row.title)} ${escape(row.id)}">${icon('arrow', 18)}</button></td>
        </tr>`;
    }

    function render() {
        const filtered = getFilteredRows();
        const pageCount = Math.max(1, Math.ceil(filtered.length / pageSize));
        state.page = Math.min(state.page, pageCount);
        const offset = (state.page - 1) * pageSize;
        const visibleRows = filtered.slice(offset, offset + pageSize);
        const redemption = state.tab === 'redemptions';
        const earning = state.tab === 'earnings';
        const firstColumn = redemption ? 'PENUKARAN' : earning ? 'AKTIVITAS' : 'TRANSAKSI';
        const middleColumn = redemption ? '' : `<th scope="col" class="${earning ? '' : 'numeric'}">${earning ? 'SUMBER' : 'TOTAL'}</th>`;

        columnsElement.innerHTML = `<th scope="col">${firstColumn}</th><th scope="col">TANGGAL</th>${middleColumn}<th scope="col" class="numeric">POIN</th><th scope="col">STATUS</th><th scope="col"><span class="sr-only">Detail</span></th>`;
        rowsElement.innerHTML = visibleRows.map(renderRow).join('');
        document.getElementById('table-caption').textContent = `Riwayat ${labels[state.tab]} member`;
        table.hidden = filtered.length === 0;
        emptyElement.hidden = filtered.length !== 0;
        const hasFilters = Boolean(state.query.trim()) || state.period !== 'all';
        emptyElement.querySelector('h3').textContent = hasFilters ? 'Belum ada aktivitas yang cocok' : 'Belum ada aktivitas';
        emptyElement.querySelector('p').textContent = hasFilters ? 'Coba kata kunci lain atau ubah periode riwayat.' : 'Transaksi dan perubahan poinmu akan tercatat di sini.';
        document.getElementById('reset-filters').hidden = !hasFilters;
        countElement.innerHTML = filtered.length
            ? `Menampilkan <strong>${offset + 1}–${offset + visibleRows.length}</strong> dari <strong>${filtered.length}</strong> ${labels[state.tab]}`
            : `Tidak ada ${labels[state.tab]} yang cocok`;

        tabs.forEach((tab) => {
            const selected = tab.dataset.tab === state.tab;
            tab.setAttribute('aria-selected', String(selected));
            tab.tabIndex = selected ? 0 : -1;
        });
        panel.setAttribute('aria-labelledby', `tab-${state.tab}`);
        pageNumbers.innerHTML = Array.from({ length: pageCount }, (_, index) => {
            const page = index + 1;
            return `<button type="button" class="page-button" data-page="${page}" aria-label="Halaman ${page}"${page === state.page ? ' aria-current="page"' : ''}>${page}</button>`;
        }).join('');
        previous.disabled = state.page === 1;
        next.disabled = state.page === pageCount;
        document.getElementById('history-pagination').hidden = filtered.length === 0;
        document.getElementById('export-button').disabled = filtered.length === 0;
    }

    function selectTab(name) {
        state.tab = name;
        state.page = 1;
        render();
    }

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => selectTab(tab.dataset.tab));
        tab.addEventListener('keydown', (event) => {
            const current = tabs.indexOf(tab);
            let target;
            if (event.key === 'ArrowRight') target = (current + 1) % tabs.length;
            if (event.key === 'ArrowLeft') target = (current + tabs.length - 1) % tabs.length;
            if (event.key === 'Home') target = 0;
            if (event.key === 'End') target = tabs.length - 1;
            if (target === undefined) return;
            event.preventDefault();
            selectTab(tabs[target].dataset.tab);
            tabs[target].focus();
        });
    });

    search.addEventListener('input', () => { state.query = search.value; state.page = 1; render(); });
    period.addEventListener('change', () => { state.period = period.value; state.page = 1; render(); });
    sort.addEventListener('change', () => { state.sort = sort.value; state.page = 1; render(); });
    document.getElementById('reset-filters').addEventListener('click', () => {
        search.value = '';
        period.value = 'all';
        sort.value = 'newest';
        if (window.jQuery?.fn.select2) window.jQuery([period, sort]).trigger('change.select2');
        Object.assign(state, { query: '', period: 'all', sort: 'newest', page: 1 });
        render();
        search.focus();
    });

    function goToPage(page) {
        state.page = page;
        render();
        // The page buttons are recreated; keep keyboard focus inside the panel.
        pageNumbers.querySelector('[aria-current="page"]')?.focus({ preventScroll: true });
    }
    pageNumbers.addEventListener('click', (event) => {
        const button = event.target.closest('[data-page]');
        if (button) goToPage(Number(button.dataset.page));
    });
    previous.addEventListener('click', () => goToPage(state.page - 1));
    next.addEventListener('click', () => goToPage(state.page + 1));

    document.querySelector('[data-view-points]').addEventListener('click', () => {
        selectTab('earnings');
        document.getElementById('activity').scrollIntoView({ behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth', block: 'start' });
        document.getElementById('tab-earnings').focus({ preventScroll: true });
    });

    document.querySelectorAll('[data-open-guide]').forEach((button) => button.addEventListener('click', () => guideDialog.showModal()));
    document.querySelectorAll('[data-close-dialog]').forEach((button) => button.addEventListener('click', () => button.closest('dialog').close()));
    [guideDialog, detailDialog].forEach((dialog) => {
        dialog.addEventListener('click', (event) => {
            const rect = dialog.getBoundingClientRect();
            if (event.target === dialog && (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom)) dialog.close();
        });
    });

    rowsElement.addEventListener('click', (event) => {
        const button = event.target.closest('[data-detail]');
        if (!button) return;
        const row = getSource().find((record) => record.id === button.dataset.detail);
        if (!row) return;
        const redemption = state.tab === 'redemptions';
        const earning = state.tab === 'earnings';
        document.getElementById('detail-kicker').textContent = redemption ? 'DETAIL PENGGUNAAN POIN' : earning ? 'DETAIL PENDAPATAN POIN' : 'DETAIL TRANSAKSI';
        document.getElementById('detail-title').textContent = row.title;
        const fields = [
            ['Nomor referensi', row.id],
            ['Tanggal', formatDate(row.date)],
            ['Kategori', row.category],
            ...(!redemption ? [['Nilai transaksi', row.amount == null ? '—' : rupiah(row.amount)], ['Pembayaran', row.payment || '—']] : []),
            [redemption ? 'Poin digunakan' : 'Poin diperoleh', `${redemption ? '−' : '+'}${number.format(row.points)} poin`],
            ['Status', redemption ? 'Ditukarkan' : earning ? 'Diterima' : 'Selesai'],
        ];
        document.getElementById('detail-content').innerHTML = `<p class="detail-meta">${redemption ? 'Poin ditukar untuk keuntungan membership.' : row.points > 0 ? 'Aktivitas selesai dan poin sudah ditambahkan.' : 'Transaksi selesai, belum menghasilkan poin.'}</p><dl class="detail-list">${fields.map(([key, value]) => `<div><dt>${escape(key)}</dt><dd>${escape(value)}</dd></div>`).join('')}</dl>${redemption && row.description ? `<p class="detail-note">${escape(row.description)}</p>` : ''}`;
        detailDialog.showModal();
    });

    function csvCell(value) {
        const text = String(value);
        const safe = /^[=+\-@\t\r]/.test(text) ? `'${text}` : text;
        return `"${safe.replace(/"/g, '""')}"`;
    }

    document.getElementById('export-button').addEventListener('click', () => {
        const filtered = getFilteredRows();
        if (!filtered.length) return;
        const redemption = state.tab === 'redemptions';
        const headings = ['Referensi', 'Tanggal', 'Aktivitas', 'Kategori', ...(!redemption ? ['Nilai transaksi (Rp)', 'Pembayaran'] : []), 'Poin', 'Status'];
        const records = filtered.map((row) => [row.id, row.date, row.title, row.category, ...(!redemption ? [row.amount ?? '', row.payment ?? ''] : []), redemption ? -row.points : row.points, redemption ? 'Ditukarkan' : state.tab === 'earnings' ? 'Diterima' : 'Selesai']);
        const csv = '\uFEFF' + [headings, ...records].map((record) => record.map(csvCell).join(',')).join('\r\n');
        const url = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8;' }));
        const link = document.createElement('a');
        link.href = url;
        link.download = `coignitive-${state.tab}-${state.period}.csv`;
        document.body.append(link);
        link.click();
        link.remove();
        setTimeout(() => URL.revokeObjectURL(url), 1000);
        const toast = document.getElementById('download-toast');
        clearTimeout(toastTimer);
        toast.hidden = false;
        toastTimer = setTimeout(() => { toast.hidden = true; }, 3500);
    });

    render();
})();
