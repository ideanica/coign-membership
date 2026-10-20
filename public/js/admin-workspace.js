document.querySelectorAll('[data-fill-dialog]').forEach(button => {
    button.addEventListener('click', () => {
        const dialog = document.getElementById(button.dataset.dialogOpen);
        const form = dialog?.querySelector('form');
        if (!form) return;
        form.reset();
        form.action = button.dataset.formAction;
        const values = JSON.parse(button.dataset.formValues);
        Object.entries(values).forEach(([name, value]) => {
            const input = form.elements.namedItem(name);
            if (!(input instanceof HTMLElement)) return;
            if (input instanceof HTMLInputElement && input.type === 'checkbox') input.checked = Boolean(value);
            else input.value = value ?? '';
        });
        if (window.jQuery?.fn.select2) window.jQuery(form).find('select').trigger('change.select2');
    }, { capture: true });
});
