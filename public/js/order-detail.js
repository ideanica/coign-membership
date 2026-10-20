(() => {
    document.querySelectorAll('[data-order-open], [data-dialog-open]').forEach(button => {
        button.addEventListener('click', () => {
            const dialog = document.getElementById(button.dataset.orderOpen || button.dataset.dialogOpen);
            if (dialog instanceof HTMLDialogElement && !dialog.open) dialog.showModal();
        });
    });
    document.querySelectorAll('.order-dialog, [data-managed-dialog]').forEach(dialog => {
        dialog.querySelectorAll('[data-order-close], [data-dialog-close]').forEach(button => {
            button.addEventListener('click', () => dialog.close());
        });
        dialog.addEventListener('click', event => {
            const bounds = dialog.getBoundingClientRect();
            if (event.target === dialog && (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom)) dialog.close();
        });
        if (dialog.hasAttribute('data-open-on-error')) dialog.showModal();
    });
})();
