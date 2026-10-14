(() => {
    if (!('IntersectionObserver' in window)) return;
    const motion = window.matchMedia('(prefers-reduced-motion: reduce)');
    if (motion.matches) return;

    const targets = [...document.querySelectorAll('.home-header, .home-hero-copy, .hero-art, .home-ticker, .home-section-heading, .home-service, .process-card, .tracking-banner, .home-membership, .testimonial-card, .home-contact > div, .home-footer > div')];
    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            entry.target.classList.remove('reveal-pending');
            observer.unobserve(entry.target);
        });
    }, { threshold: 0.08 });

    targets.forEach(element => {
        element.classList.add('scroll-reveal', 'reveal-pending');
        observer.observe(element);
    });

    const revealAll = () => targets.forEach(element => element.classList.remove('reveal-pending'));
    document.addEventListener('focusin', event => {
        event.target.closest('.reveal-pending')?.classList.remove('reveal-pending');
    });
    motion.addEventListener('change', event => {
        if (event.matches) { revealAll(); observer.disconnect(); }
    });
    window.addEventListener('beforeprint', revealAll);
})();
