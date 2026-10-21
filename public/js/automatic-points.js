document.querySelectorAll('[data-tier-rule-form]').forEach(form => {
    const type = form.querySelector('[data-reward-type]');
    const fields = form.querySelector('[data-multiple-fields]');
    const update = () => {
        const multiple = type.value === 'multiple';
        fields.hidden = !multiple;
        fields.querySelectorAll('input').forEach(input => { input.disabled = !multiple; });
        form.elements.step_amount.required = multiple;
    };
    type.addEventListener('change', update);
    form.addEventListener('reset', () => setTimeout(update, 0));
    document.querySelectorAll('[data-fill-dialog]').forEach(button => button.addEventListener('click', () => setTimeout(update, 0)));
    update();
});

const purchaseForm = document.querySelector('[data-point-purchase]');
if (purchaseForm) {
    const rules = JSON.parse(document.querySelector('#point-rule-data').textContent).sort((a, b) => b.minimum - a.minimum);
    const amountInput = purchaseForm.querySelector('[name="amount"]');
    const update = () => {
        const amount = Number(amountInput.value) || 0;
        const rule = rules.find(rule => amount >= rule.minimum && (rule.maximum === null || amount <= rule.maximum));
        const tierPoints = rule ? (rule.type === 'multiple' ? Math.min(rule.cap, Math.floor(amount / rule.step) * rule.points) : rule.points) : 0;
        const points = purchaseForm.dataset.mode === 'tiers' ? tierPoints : Math.floor(amount / Number(purchaseForm.dataset.ratio));
        purchaseForm.querySelector('[data-points-preview]').textContent = `+${Math.max(0, points).toLocaleString('id-ID')} poin`;
        purchaseForm.querySelector('[data-points-description]').textContent = purchaseForm.dataset.mode === 'tiers'
            ? (rule ? `Aturan ${rule.name}: Rp${rule.minimum.toLocaleString('id-ID')} sampai ${rule.maximum === null ? 'tanpa batas atas' : 'Rp' + rule.maximum.toLocaleString('id-ID')}. ${rule.type === 'multiple' ? 'Kelipatan dihitung dari total pembelian, dibulatkan ke bawah, dan dibatasi sesuai aturan.' : 'Hadiah tetap diberikan satu kali.'}` : 'Nominal berada di luar rentang aturan aktif; tidak mendapat poin.')
            : 'Estimasi berdasarkan rasio rupiah yang tersimpan.';
    };
    amountInput.addEventListener('input', update);
    update();
}
