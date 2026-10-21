document.querySelectorAll('input[type="password"]').forEach((input, index) => {
    const wrapper = document.createElement('div');
    wrapper.className = 'password-input';
    input.before(wrapper);
    wrapper.append(input);
    if (!input.id) input.id = `password-field-${index}`;

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'password-toggle';
    button.setAttribute('aria-controls', input.id);
    const eye = '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>';
    const eyeOff = '<path d="m3 3 18 18M10.6 5.1 12 5c6.5 0 10 7 10 7a18 18 0 0 1-3.2 4M6.2 6.2A19 19 0 0 0 2 12s3.5 7 10 7a12 12 0 0 0 5.8-1.8M9.9 9.9a3 3 0 0 0 4.2 4.2"/>';
    const update = () => {
        const visible = input.type === 'text';
        const label = visible ? window.siteText('Sembunyikan kata sandi') : window.siteText('Tampilkan kata sandi');
        button.setAttribute('aria-label', label);
        button.setAttribute('aria-pressed', String(visible));
        button.title = label;
        button.innerHTML = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${visible ? eyeOff : eye}</svg>`;
    };

    button.addEventListener('click', () => {
        input.type = input.type === 'password' ? 'text' : 'password';
        update();
    });
    input.form?.addEventListener('reset', () => {
        input.type = 'password';
        update();
    });
    update();
    wrapper.append(button);
});
