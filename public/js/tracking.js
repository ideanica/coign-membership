const orderBilling = document.querySelector('[data-order-billing]');
if (orderBilling) {
    const form = orderBilling.closest('form');
    const discount = form.querySelector('#discount');
    const items = orderBilling.querySelector('[data-order-items]');
    const addButton = orderBilling.querySelector('[data-add-order-item]');
    const money = value => `Rp${value.toLocaleString('id-ID')}`;
    const update = () => {
        const rows = [...items.querySelectorAll('[data-order-item]')];
        let subtotal = 0;
        rows.forEach((row, index) => {
            row.querySelector('[data-item-number]').textContent = index + 1;
            row.querySelectorAll('[data-item-field]').forEach(input => {
                const field = input.dataset.itemField;
                input.name = `items[${index}][${field}]`;
                input.id = `item-${index}-${field}`;
                input.previousElementSibling.htmlFor = input.id;
            });
            const amount = Number(row.querySelector('[data-item-field="quantity"]').value) * Number(row.querySelector('[data-item-field="unit_price"]').value);
            subtotal += amount;
            row.querySelector('[data-item-subtotal]').textContent = money(amount);
            row.querySelector('[data-remove-order-item]').disabled = rows.length === 1;
        });
        addButton.disabled = rows.length >= 100;
        discount.setCustomValidity(subtotal > 10000000000000 ? 'Subtotal pesanan maksimal Rp10.000.000.000.000.' : Number(discount.value) >= subtotal && subtotal > 0 ? 'Diskon harus lebih kecil dari subtotal.' : '');
        form.querySelector('[data-order-subtotal]').textContent = money(subtotal);
        form.querySelector('[data-order-total]').textContent = money(Math.max(0, subtotal - Number(discount.value)));
    };
    addButton.addEventListener('click', () => {
        if (items.querySelectorAll('[data-order-item]').length >= 100) return;
        const fragment = orderBilling.querySelector('[data-order-item-template]').content.cloneNode(true);
        const row = fragment.querySelector('[data-order-item]');
        items.append(fragment);
        update();
        row.querySelector('[data-item-field="name"]').focus();
    });
    items.addEventListener('click', event => {
        const button = event.target.closest('[data-remove-order-item]');
        if (!button || items.querySelectorAll('[data-order-item]').length <= 1) return;
        const row = button.closest('[data-order-item]');
        const next = row.nextElementSibling || row.previousElementSibling;
        row.remove();
        update();
        next.querySelector('[data-item-field="name"]').focus();
    });
    items.addEventListener('input', update);
    discount.addEventListener('input', update);
    const member = form.querySelector('#user_id');
    const customer = form.querySelector('#customer_name');
    const email = form.querySelector('#customer_email');
    const guestFields = form.querySelector('[data-guest-customer]');
    const memberSummary = form.querySelector('[data-member-customer]');
    const identity = () => {
        const selected = member.selectedOptions[0];
        const hasMember = Boolean(member.value);
        guestFields.hidden = hasMember;
        memberSummary.hidden = !hasMember;
        customer.required = !hasMember;
        customer.disabled = email.disabled = hasMember;
        memberSummary.querySelector('[data-member-customer-name]').textContent = hasMember ? selected.dataset.customerName : '';
        memberSummary.querySelector('[data-member-customer-email]').textContent = hasMember ? selected.dataset.customerEmail : '';
    };
    member.addEventListener('change', identity);
    form.addEventListener('reset', () => setTimeout(() => { identity(); update(); }, 0));
    identity();
    update();
}
document.querySelectorAll('[data-copy-reference]').forEach(button => {
    button.addEventListener('click', async () => {
        const status = document.querySelector('[data-copy-status]');
        try {
            await navigator.clipboard.writeText(button.dataset.copyReference);
            button.textContent = window.siteText('Tersalin!');
            status.textContent = window.siteText('Nomor tracking berhasil disalin.');
        } catch {
            button.textContent = window.siteText('Gagal disalin');
            status.textContent = window.siteText('Tidak dapat mengakses clipboard. Silakan salin nomor tracking secara manual.');
        }
        setTimeout(() => { button.textContent = window.siteText('Salin'); }, 2500);
    });
});
