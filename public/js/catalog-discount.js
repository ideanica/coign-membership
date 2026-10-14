(() => {
    const form = document.querySelector('[data-discount-form]');
    if(!form) return;
    const catalog = form.querySelector('#catalog_id'), type = form.querySelector('#type'), value = form.querySelector('#value');
    const update = () => {
        const price = Number(catalog.selectedOptions[0]?.dataset.price ?? 0);
        const amount = Math.max(0, Number(value.value) || 0);
        value.max = type.value === 'percent' ? 100 : 1000000000;
        const saving = Math.min(price, type.value === 'percent' ? Math.round(price * Math.min(100,amount) / 100) : amount);
        form.querySelector('[data-discount-price]').textContent = `Rp${(price - saving).toLocaleString('id-ID')}`;
        form.querySelector('[data-discount-saving]').textContent = `Harga normal Rp${price.toLocaleString('id-ID')} · Hemat Rp${saving.toLocaleString('id-ID')}`;
    };
    [catalog, type, value].forEach(input=>input.addEventListener('input',update)); update();
})();
