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
    const identity = () => { customer.required = !member.value; };
    member.addEventListener('change', identity);
    identity();
    [price, quantity, discount].forEach(input => input.addEventListener('input', update));
    update();
}
document.querySelectorAll('[data-copy-reference]').forEach(button => {
    button.addEventListener('click', async () => {
        const status = document.querySelector('[data-copy-status]');
        try {
            await navigator.clipboard.writeText(button.dataset.copyReference);
            button.textContent = 'Tersalin!';
            status.textContent = 'Nomor tracking berhasil disalin.';
        } catch {
            button.textContent = 'Gagal disalin';
            status.textContent = 'Tidak dapat mengakses clipboard. Silakan salin nomor tracking secara manual.';
        }
        setTimeout(() => { button.textContent = 'Salin'; }, 2500);
    });
});
