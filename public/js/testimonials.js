(() => {
    const list = document.querySelector('[data-review-list]');
    const toggle = document.querySelector('.testimonial-list-toggle');
    if (list && toggle) {
        const mobile = matchMedia('(max-width: 560px)');
        let expanded = false, loading = false, next = list.dataset.nextUrl;
        list.parentElement.classList.add('testimonial-list-enhanced');
        const render = () => {
            list.classList.toggle('reviews-collapsed', mobile.matches && !expanded);
            toggle.hidden = !mobile.matches;
            toggle.disabled = loading;
            toggle.setAttribute('aria-expanded', String(expanded));
            toggle.querySelector('[data-review-toggle-label]').textContent = loading ? window.siteText('Memuat...') : expanded ? window.siteText('Show less') : window.siteText('Show more');
        };
        toggle.addEventListener('click', async () => {
            expanded = !expanded;
            const status = list.parentElement.querySelector('.testimonial-list-status');
            status.textContent = '';
            loading = expanded && Boolean(next);
            render();
            try {
                while (loading && next) {
                    const url = new URL(next, location.href);
                    if (url.origin !== location.origin) throw new Error('Invalid origin');
                    const response = await fetch(url, { headers: { Accept: 'text/html' } });
                    if (!response.ok) throw new Error('Request failed');
                    const page = new DOMParser().parseFromString(await response.text(), 'text/html');
                    const more = page.querySelector('[data-review-list]');
                    if (!more) throw new Error('Missing reviews');
                    more.querySelectorAll(':scope > .testimonial-card').forEach(card => list.append(document.importNode(card, true)));
                    next = more.dataset.nextUrl;
                }
            } catch {
                status.textContent = window.siteText('Sebagian testimoni belum dapat dimuat. Tutup lalu coba Show more lagi.');
            } finally { loading = false; render(); }
        });
        mobile.addEventListener('change', render);
        render();
    }
    const panel = document.querySelector('#write-testimonial');
    if (!panel) return;
    document.querySelectorAll('a[href="#write-testimonial"]').forEach(link => link.addEventListener('click', () => { panel.open = true; }));
    if (location.hash === '#write-testimonial') panel.open = true;
    const text = panel.querySelector('#review-comment');
    const count = panel.querySelector('[data-comment-count]');
    const update = () => { count.textContent = [...text.value].length; };
    text.addEventListener('input', update);
    update();
})();
