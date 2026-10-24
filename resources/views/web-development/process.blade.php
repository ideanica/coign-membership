<section class="home-container web-section" aria-label="How We Work">
    <x-section-heading eyebrow="HOW WE WORK" title="Dari obrolan pertama sampai launch.">{{ __('Langkah yang jelas, dengan ruang untuk review di sepanjang proses.') }}</x-section-heading>
    <ol class="web-process-grid">@foreach(__('web-development.process') as $title => $description)<li class="card"><span class="web-process-number">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><h3>{{ $title }}</h3><p>{{ $description }}</p></li>@endforeach</ol>
</section>
