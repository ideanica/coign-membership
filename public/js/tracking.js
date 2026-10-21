const orderBilling = document.querySelector('[data-order-billing]');
if (orderBilling) {
    const form = orderBilling.closest('form');
    const price = form.querySelector('#unit_price');
    const discount = form.querySelector('#discount');
    const quantity = form.querySelector('#quantity');
    const update = () => {
        const subtotal = Number(price.value) * Number(quantity.value);
        discount.setCustomValidity(Number(discount.value) >= subtotal && subtotal > 0 ? 'Diskon harus lebih kecil dari subtotal.' : '');
        form.querySelector('[data-order-total]').textContent = `Rp${Math.max(0, subtotal - Number(discount.value)).toLocaleString('id-ID')}`;
    };
    form.querySelector('#service_id')?.addEventListener('change', event => {
        const selected = event.target.selectedOptions[0];
        if (selected?.dataset.price) price.value = selected.dataset.price;
        update();
    });
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
    [price, quantity, discount].forEach(input => input.addEventListener('input', update));
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
