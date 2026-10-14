const purchaseForm = document.querySelector('[data-point-purchase]');
if (purchaseForm) {
    const rules = JSON.parse(document.querySelector('#point-rule-data').textContent).sort((a, b) => b.minimum - a.minimum);
    const amountInput = purchaseForm.querySelector('[name="amount"]');
    const update = () => {
        const amount = Number(amountInput.value) || 0;
        const rule = rules.find(rule => amount >= rule.minimum);
        const points = purchaseForm.dataset.mode === 'tiers' ? (rule?.points ?? 0) : Math.floor(amount / Number(purchaseForm.dataset.ratio));
        purchaseForm.querySelector('[data-points-preview]').textContent = `+${Math.max(0, points).toLocaleString('id-ID')} poin`;
        purchaseForm.querySelector('[data-points-description]').textContent = purchaseForm.dataset.mode === 'tiers' ? (rule ? `Batas terpenuhi: minimal Rp${rule.minimum.toLocaleString('id-ID')}.` : 'Belum memenuhi batas aturan aktif.') : 'Estimasi berdasarkan rasio rupiah yang tersimpan.';
    };
    amountInput.addEventListener('input', update);
    update();
}
