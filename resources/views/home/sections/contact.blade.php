<section class="home-container home-contact" id="kontak">
    <div class="home-contact-copy">
        <h2><span class="contact-title-intro">Ide berikutnya,</span><span class="contact-title-line">bisa jadi</span><span class="contact-title-highlight">punyamu.</span><span class="contact-title-star" aria-hidden="true">✦</span></h2>
        <p>Ceritakan idemu, gambaran konsep, dan kapan kamu membutuhkannya. Kita mulai dari obrolan kecil, lalu bikin sesuatu yang berarti.</p>
        <div class="contact-links">
            @if(filled($settings['support_whatsapp'] ?? ''))
                <a class="button contact-link contact-whatsapp" href="https://wa.me/{{ ltrim($settings['support_whatsapp'], '+') }}" target="_blank" rel="noopener noreferrer">WhatsApp <x-icon name="arrow-up-right" size="16" /><span class="sr-only">Buka di tab baru</span></a>
            @endif
            @if(filled($settings['support_email'] ?? ''))
                <a class="button contact-link" href="mailto:{{ $settings['support_email'] }}?subject={{ rawurlencode('Diskusi layanan '.$brand) }}">Email <x-icon name="arrow-up-right" size="16" /></a>
            @endif
            @if(filled($settings['support_x'] ?? ''))
                <a class="button button-neutral contact-link" href="https://x.com/{{ ltrim($settings['support_x'], '@') }}" target="_blank" rel="noopener noreferrer">X <x-icon name="arrow-up-right" size="16" /><span class="sr-only">Buka di tab baru</span></a>
            @endif
        </div>
    </div>
    <div class="home-full-logo"><x-brand-logo full :settings="$settings" /><span>Website, moments, and a little bit of magic.</span></div>
</section>
