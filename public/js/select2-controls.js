(() => {
    const $ = window.jQuery;
    if (!$?.fn.select2) return;

    const initialize = root => {
        const selects = root.matches?.('select') ? [root] : root.querySelectorAll('select');
        selects.forEach(select => {
            if (select.closest('template') || $(select).data('select2')) return;
            const dialog = select.closest('dialog');
            $(select).select2({
                language: document.documentElement.lang,
                width: '100%',
                dropdownParent: dialog ? $(dialog) : $(document.body),
                placeholder: select.multiple ? (select.dataset.placeholder || window.siteText('Pilih opsi')) : undefined,
                closeOnSelect: !select.multiple,
                minimumResultsForSearch: 0,
                matcher: (params, data) => data.element?.hidden ? null : $.fn.select2.defaults.defaults.matcher(params, data),
            });
            const selection = $(select).data('select2').$selection[0];
            const label = select.labels?.[0];
            if (label) {
                if (!label.id) label.id = `select-label-${select.id || Math.random().toString(36).slice(2)}`;
                selection.setAttribute('aria-labelledby', label.id);
            }
            if (select.required) selection.setAttribute('aria-required', 'true');
            $(select).on('change.select2Native', event => {
                // Existing bill, promo and history handlers listen for native events.
                if (!event.originalEvent && !event.namespace) {
                    select.dispatchEvent(new Event('input', { bubbles: true }));
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
            select.addEventListener('invalid', event => {
                event.preventDefault();
                selection.setAttribute('aria-invalid', 'true');
                selection.focus();
                $(select).select2('open');
            });
            select.addEventListener('change', () => selection.removeAttribute('aria-invalid'));
            $(select).on('select2:open', () => {
                const search = document.querySelector('.select2-container--open .select2-search__field');
                if (search) { search.placeholder = window.siteText('Cari pilihan...'); search.focus(); }
            });
        });
    };

    initialize(document);
    new MutationObserver(records => {
        records.forEach(record => record.addedNodes.forEach(node => {
            if (node.nodeType === Node.ELEMENT_NODE && (node.matches('select') || node.querySelector('select'))) initialize(node);
        }));
    }).observe(document.body, { childList: true, subtree: true });

    document.addEventListener('reset', event => {
        setTimeout(() => $(event.target).find('select').trigger('change.select2'), 0);
    });
})();
