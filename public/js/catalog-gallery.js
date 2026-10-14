(() => {
    const mobile = matchMedia('(max-width: 560px)');
    const reduced = matchMedia('(prefers-reduced-motion: reduce)');
    const gallery = document.querySelector('.catalog-gallery');
    const image = gallery?.querySelector('[data-gallery-main]');
    const buttons = [...document.querySelectorAll('[data-gallery-src]')];
    let current = 0, timer, visible = true, paused = reduced.matches;
    const select = index => {
        if (!image || !buttons[index]) return;
        current = index;
        const button = buttons[index];
        image.src = button.dataset.gallerySrc;
        image.alt = button.dataset.galleryAlt;
        const counter = gallery.querySelector('[data-gallery-counter]');
        if (counter) counter.textContent = `${String(index + 1).padStart(2, '0')} / ${String(buttons.length).padStart(2, '0')}`;
        buttons.forEach(item => item.setAttribute('aria-pressed', String(item === button)));
        if (mobile.matches) {
            const strip = button.parentElement;
            strip.scrollTo({ left: Math.max(0, button.offsetLeft - (strip.clientWidth - button.offsetWidth) / 2), behavior: reduced.matches ? 'instant' : 'smooth' });
        }
    };
    const schedule = () => {
        clearInterval(timer);
        if (mobile.matches && buttons.length > 1 && visible && !paused && !document.hidden) timer = setInterval(() => select((current + 1) % buttons.length), 4500);
    };
    buttons.forEach((button, index) => button.addEventListener('click', () => { select(index); schedule(); }));
    document.addEventListener('visibilitychange', schedule);
    if (gallery) new IntersectionObserver(entries => { visible = entries[0].isIntersecting; schedule(); }).observe(gallery);
    reduced.addEventListener('change', () => { paused = reduced.matches; schedule(); });
    const description = document.querySelector('.catalog-description');
    const more = document.querySelector('.catalog-description-toggle');
    const features = document.querySelector('#template-features');
    let expanded = false;
    const layout = () => {
        description?.classList.toggle('description-collapsed', mobile.matches && !expanded);
        if (more && description) {
            more.hidden = !mobile.matches || (!expanded && description.scrollHeight <= description.clientHeight + 1);
            more.querySelector('[data-description-label]').textContent = expanded ? 'Show less' : 'Show more';
            more.setAttribute('aria-expanded', String(expanded));
        }
    };
    more?.addEventListener('click', () => { expanded = !expanded; layout(); });
    const breakpoint = () => { if (features) features.open = !mobile.matches; layout(); schedule(); };
    mobile.addEventListener('change', breakpoint);
    window.addEventListener('resize', layout);
    document.fonts.ready.then(layout);
    document.querySelectorAll('a[href="#template-features"]').forEach(link => link.addEventListener('click', () => { if (features) features.open = true; }));
    breakpoint();
    if (location.hash === '#template-features' && features) features.open = true;
})();
