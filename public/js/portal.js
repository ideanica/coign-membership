(() => {
    const modal = document.getElementById('action-confirm');
    let pendingForm;
    document.querySelectorAll('form[data-confirm]').forEach(form => {
        form.addEventListener('submit', event => {
            if (form.dataset.confirmed === 'true') return;
            event.preventDefault();
            pendingForm = form;
            document.getElementById('confirm-message').textContent = form.dataset.confirm;
            modal.showModal();
        });
    });
    document.getElementById('confirm-cancel')?.addEventListener('click', () => { pendingForm = null; modal.close(); });
    document.getElementById('confirm-submit')?.addEventListener('click', () => {
        if (!pendingForm) return;
        const form = pendingForm;
        pendingForm = null;
        form.dataset.confirmed = 'true';
        modal.close();
        form.requestSubmit();
    });
    document.querySelector('[data-print]')?.addEventListener('click', () => window.print());
    const member = document.querySelector('[data-bill-member]');
    const tracking = document.querySelector('[data-bill-tracking]');
    const filterTracking = () => {
        if (!member || !tracking) return;
        [...tracking.options].forEach(option => { option.hidden = Boolean(option.value && option.dataset.member !== member.value); option.disabled = option.hidden; });
        if (tracking.selectedOptions[0]?.disabled) tracking.value = '';
        if (window.jQuery?.fn.select2) window.jQuery(tracking).trigger('change.select2');
    };
    member?.addEventListener('change', filterTracking);
    filterTracking();
    document.querySelector('[data-bill-service]')?.addEventListener('change', event => {
        const option = event.target.selectedOptions[0];
        if (!option.value) return;
        document.getElementById('unit_price').value = option.dataset.price;
        if (!document.getElementById('title').value) document.getElementById('title').value = option.dataset.name;
        previewTotal();
    });
    function previewTotal() {
        const target = document.getElementById('bill-total-preview');
        if (!target) return;
        const quantity = Number(document.getElementById('quantity').value);
        const price = Number(document.getElementById('unit_price').value);
        const discount = Number(document.getElementById('discount').value);
        target.textContent = 'Rp' + new Intl.NumberFormat('id-ID').format(quantity * price - discount);
    }
    ['quantity', 'unit_price', 'discount'].forEach(id => document.getElementById(id)?.addEventListener('input', previewTotal));
    previewTotal();
})();
