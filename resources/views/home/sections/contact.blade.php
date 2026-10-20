<section class="contact-background" id="kontak">
    <div class="home-container home-contact">
    <svg class="contact-logo-star" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" width="330" height="330" aria-hidden="true" focusable="false">
        <path fill="currentColor" stroke="black" stroke-width="2" fill-rule="evenodd" clip-rule="evenodd" d="M107.6 5H92.4v76.652L38.199 27.451 27.45 38.199 81.65 92.4H5v15.2h76.652L27.45 161.801l10.748 10.748L92.4 118.348V195h15.2v-76.652l54.201 54.201 10.748-10.748-54.201-54.201H195V92.4h-76.652l54.201-54.201-10.748-10.748-54.201 54.2z" />
    </svg>
    <div class="home-contact-copy">
        <svg class="contact-heading-star" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" width="90" height="90" aria-hidden="true" focusable="false">
            <path fill="currentColor" stroke="black" stroke-width="2" fill-rule="evenodd" clip-rule="evenodd" d="M107.6 5H92.4v76.652L38.199 27.451 27.45 38.199 81.65 92.4H5v15.2h76.652L27.45 161.801l10.748 10.748L92.4 118.348V195h15.2v-76.652l54.201 54.201 10.748-10.748-54.201-54.201H195V92.4h-76.652l54.201-54.201-10.748-10.748-54.201 54.2z" />
        </svg>
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
        <svg class="contact-bottom-star" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" width="200" height="200" aria-hidden="true" focusable="false">
            <path fill="currentColor" stroke="black" stroke-width="2" fill-rule="evenodd" clip-rule="evenodd" d="M107.6 5H92.4v76.652L38.199 27.451 27.45 38.199 81.65 92.4H5v15.2h76.652L27.45 161.801l10.748 10.748L92.4 118.348V195h15.2v-76.652l54.201 54.201 10.748-10.748-54.201-54.201H195V92.4h-76.652l54.201-54.201-10.748-10.748-54.201 54.2z" />
        </svg>
    </div>
    <div class="contact-logo-wrap">
        <svg class="contact-logo-left-star" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" width="220" height="220" aria-hidden="true" focusable="false">
            <path fill="currentColor" stroke="black" stroke-width="2" fill-rule="evenodd" clip-rule="evenodd" d="M107.6 5H92.4v76.652L38.199 27.451 27.45 38.199 81.65 92.4H5v15.2h76.652L27.45 161.801l10.748 10.748L92.4 118.348V195h15.2v-76.652l54.201 54.201 10.748-10.748-54.201-54.201H195V92.4h-76.652l54.201-54.201-10.748-10.748-54.201 54.2z" />
        </svg>
        <svg class="contact-top-star" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" width="600" height="600" aria-hidden="true" focusable="false">
            <path fill="currentColor" stroke="black" stroke-width="2" fill-rule="evenodd" clip-rule="evenodd" d="M107.6 5H92.4v76.652L38.199 27.451 27.45 38.199 81.65 92.4H5v15.2h76.652L27.45 161.801l10.748 10.748L92.4 118.348V195h15.2v-76.652l54.201 54.201 10.748-10.748-54.201-54.201H195V92.4h-76.652l54.201-54.201-10.748-10.748-54.201 54.2z" />
        </svg>
        <div class="home-full-logo"><x-brand-logo full :settings="$settings" /><span>Website, moments, and a little bit of magic.</span></div>
    </div>
    </div>
</section>
