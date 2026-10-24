<section class="home-container web-section" aria-label="{{ __('Domain dan hosting') }}">
    <div class="card web-infrastructure"><span class="section-kicker">NO HIDDEN RECURRING COSTS</span><h2>Domain & Hosting</h2><p>{{ __('Harga development tidak otomatis termasuk domain, hosting / VPS, business email, premium plugin / service, external API, payment gateway fees, third-party subscriptions, maupun premium stock assets.') }}</p><p>{{ __('Gunakan infrastructure sendiri atau minta bantuan setup. Semua recurring costs diinformasikan terlebih dahulu; domain dan hosting bukan gratis selamanya.') }}</p></div>
</section>
<section class="home-container web-section" aria-label="{{ __('Ketentuan proyek') }}">
    <x-section-heading eyebrow="CLEAR FROM THE START" title="Kesepakatan yang bikin nyaman.">{{ __('Scope, waktu, dan dukungan disepakati bersama sebelum development dimulai.') }}</x-section-heading>
    <div class="web-policy-grid">
        <article class="card"><h3>Payment Terms</h3><div class="web-payment"><p><strong>50%</strong>Project Start</p><p><strong>50%</strong>Before Production Launch</p></div><p>{{ __('Custom project yang lebih besar dapat dibayar berdasarkan milestone. Development dimulai setelah initial payment diterima, scope disetujui, dan material utama client tersedia.') }}</p></article>
        <x-faq :items="[
            'Timeline Policy' => __('Timeline adalah estimasi, bukan guaranteed fixed deadline. Berlaku jika content dan asset tersedia, feedback tepat waktu, dan tidak ada perubahan scope besar.').' '.__('Waktu menunggu text content, images, logo, account credentials, feedback, dan approval tidak dihitung sebagai production time.'),
            'Revision Policy' => __('Satu revision round adalah satu kumpulan feedback yang diberikan sekaligus. Meliputi penyesuaian teks, penggantian gambar, serta minor layout dan visual adjustments.').' '.__('Fitur baru, halaman baru, perubahan architecture, dan perubahan requirement besar berada di luar revision. Perubahan di luar scope dapat dikenakan additional quotation.'),
            'Post-launch Support' => __('Mencakup bug fixing, technical issue, minor technical correction, dan assistance terkait hasil development selama periode support paket.').' '.__('Tidak mencakup fitur atau halaman baru, redesign, massive content update, perubahan workflow, maupun perubahan third-party services.'),
        ]" />
    </div>
</section>
