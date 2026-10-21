@extends('layouts.member')
@section('title', __('Profil & poin'))
@section('meta-description', __('Profil membership: pantau saldo poin, transaksi, penukaran, dan pendapatan poin dalam satu tempat.'))
@push('scripts')<script src="{{ asset('js/membership.js') }}?v={{ filemtime(public_path('js/membership.js')) }}" defer></script>@endpush
@section('content')
<div class="page-heading">
            <div>
                <p class="eyebrow"><span class="tiny-star">✳</span> YOUR MEMBER SPACE</p>
                <h1>{{ __('Profil & poin') }}<span class="heading-dot">.</span></h1>
                <p class="page-description">{{ __('Kenali profilmu. Pantau poinmu. Nikmati keuntungannya.') }}</p>
            </div>
            <span class="badge active-badge"><span class="status-dot"></span> {{ __('Member aktif') }}</span>
        </div>

        <section class="profile-card neo-card" aria-label="{{ __('Profil dan ringkasan poin member') }}">
            <div class="profile-info">
                <div class="profile-topline"><span class="section-kicker">{{ __('PROFIL MEMBER') }}</span><span class="member-number">{{ $member['number'] }}</span></div>
                <div class="identity">
                    <div class="avatar-frame"><img src="{{ asset($member['photo']) }}" width="92" height="92" alt="{{ __('Foto profil ilustrasi') }} {{ $member['name'] }}"><span class="avatar-check"><x-icon name="check" size="13" /></span></div>
                    <div class="identity-copy">
                        <h2>{{ $member['name'] }}</h2>
                        <span class="badge member-badge"><x-icon name="star" size="14" /> {{ $member['tier'] }}</span>
                        <p class="member-email"><x-icon name="mail" size="15" /><span>{{ $member['email'] }}</span></p>
                    </div>
                </div>
                <p class="joined"><x-icon name="calendar" size="15" /> {{ __('Bergabung sejak') }} {{ $member['joined'] }}</p>
                <div class="point-stats">
                    <div class="point-stat earned-stat">
                        <span class="stat-icon"><x-icon name="arrow-down-left" size="18" /></span>
                        <div><p>{{ __('Total poin diperoleh') }}</p><strong>+{{ number_format($summary['earned'], 0, ',', '.') }} <span>{{ __('poin') }}</span></strong></div>
                    </div>
                    <div class="point-stat used-stat">
                        <span class="stat-icon"><x-icon name="arrow-up-right" size="18" /></span>
                        <div><p>{{ __('Total poin digunakan') }}</p><strong>−{{ number_format($summary['used'], 0, ',', '.') }} <span>{{ __('poin') }}</span></strong></div>
                    </div>
                </div>
            </div>
            <div class="balance-panel">
                <div class="balance-heading"><span class="section-kicker">{{ __('POIN TERSEDIA') }}</span><span class="balance-star"><x-icon name="spark" size="36" /></span></div>
                <div class="balance-number">{{ number_format($summary['balance'], 0, ',', '.') }}<span>{{ __('poin') }}</span></div>
                <p class="balance-description">{{ __('Hasil aktivitasmu, siap jadi keuntungan baru.') }}</p>
                <button class="button button-neutral balance-button" type="button" data-view-points>{{ __('Lihat pendapatan poin') }} <x-icon name="arrow-up-right" size="18" /></button>
                <p class="balance-note"><x-icon name="info" size="14" /> {{ __('Saldo = poin diperoleh − poin digunakan') }}</p>
            </div>
        </section>

        <aside class="points-tip">
            <span class="tip-icon"><x-icon name="gift" size="23" /></span>
            <p><strong>{{ __('Aktivitas seru, bonusnya poin.') }}</strong><span> {{ __('Setiap transaksi bisa membawamu ke keuntungan berikutnya.') }}</span></p>
            <button type="button" class="text-button" data-open-guide>{{ __('Cara kerja poin') }} <x-icon name="arrow-right" size="17" /></button>
        </aside>

        <section id="activity" class="activity-section" aria-labelledby="activity-heading">
            <div class="activity-heading">
                <div><p class="section-kicker">{{ __('KEEP TRACK OF THE GOOD STUFF') }}</p><h2 id="activity-heading">{{ __('Semua aktivitasmu') }}</h2><p>{{ __('Transaksi dan perjalanan poin, tercatat di sini.') }}</p></div>
                <button type="button" class="button button-neutral export-button" id="export-button"><x-icon name="download" size="17" /> {{ __('Unduh riwayat') }}</button>
            </div>
            <div class="history-card neo-card">
                <div class="history-tabs" role="tablist" aria-label="Jenis riwayat">
                    <button id="tab-transactions" class="history-tab" type="button" role="tab" aria-selected="true" aria-controls="history-panel" data-tab="transactions"><x-icon name="receipt" size="18" /><span>{{ __('Riwayat transaksi') }}</span><span class="tab-count">{{ count($transactions) }}</span></button>
                    <button id="tab-redemptions" class="history-tab" type="button" role="tab" aria-selected="false" aria-controls="history-panel" tabindex="-1" data-tab="redemptions"><x-icon name="gift" size="18" /><span>{{ __('Penggunaan poin') }}</span><span class="tab-count">{{ count($redemptions) }}</span></button>
                    <button id="tab-earnings" class="history-tab" type="button" role="tab" aria-selected="false" aria-controls="history-panel" tabindex="-1" data-tab="earnings"><x-icon name="star" size="18" /><span>{{ __('Pendapatan poin') }}</span><span class="tab-count">{{ count($earnings) }}</span></button>
                </div>
                <div id="history-panel" role="tabpanel" aria-labelledby="tab-transactions" tabindex="0">
                    <div class="history-toolbar">
                        <div class="search-field"><x-icon name="search" size="18" /><label class="sr-only" for="history-search">{{ __('Cari riwayat berdasarkan nama, nomor, atau tanggal') }}</label><input id="history-search" type="search" placeholder="Cari transaksi atau aktivitas…" autocomplete="off"></div>
                        <div class="history-filters">
                            <div class="select-field"><x-icon name="calendar" size="16" /><label class="sr-only" for="history-period">{{ __('Filter periode riwayat') }}</label><select id="history-period"><option value="all">{{ __('Semua periode') }}</option>@foreach($periods as $value => $label)<option value="{{ $value }}">{{ __($label) }}</option>@endforeach</select><x-icon name="chevron-down" size="15" /></div>
                            <div class="select-field sort-field"><label class="sr-only" for="history-sort">{{ __('Urutan riwayat') }}</label><select id="history-sort"><option value="newest">{{ __('Terbaru') }}</option><option value="oldest">{{ __('Terlama') }}</option></select><x-icon name="chevron-down" size="15" /></div>
                        </div>
                    </div>
                    <p class="table-scroll-hint"><x-icon name="arrow-right" size="13" /> {{ __('Geser tabel untuk melihat semua kolom.') }}</p>
                    <div class="table-scroller">
                        <table class="history-table">
                            <caption id="table-caption" class="sr-only">{{ __('Riwayat transaksi member') }}</caption>
                            <thead><tr id="history-columns"><th scope="col">{{ __('TRANSAKSI') }}</th><th scope="col">{{ __('TANGGAL') }}</th><th scope="col" class="numeric">TOTAL</th><th scope="col" class="numeric">{{ __('POIN') }}</th><th scope="col">STATUS</th><th scope="col"><span class="sr-only">{{ __('Detail') }}</span></th></tr></thead>
                            <tbody id="history-rows">
                                @foreach(array_slice($transactions, 0, 6) as $transaction)
                                    <tr>
                                        <td><div class="activity-cell"><span class="row-icon category-{{ strtolower($transaction['category']) }}"><x-icon name="receipt" size="19" /></span><div><strong>{{ $transaction['title'] }}</strong><span>{{ $transaction['id'] }}</span></div></div></td>
                                        <td><time datetime="{{ $transaction['date'] }}">{{ \Carbon\CarbonImmutable::parse($transaction['date'])->locale(app()->getLocale())->translatedFormat('d M Y') }}</time></td>
                                        <td class="numeric amount-cell">Rp{{ number_format($transaction['amount'], 0, ',', '.') }}</td>
                                        <td class="numeric"><span class="point-change positive">+{{ number_format($transaction['points'], 0, ',', '.') }}</span></td>
                                        <td><span class="badge success-badge"><x-icon name="check" size="12" /> {{ __('Selesai') }}</span></td>
                                        <td><button class="detail-button" type="button" data-detail="{{ $transaction['id'] }}" aria-label="{{ __('Lihat detail') }} {{ $transaction['title'] }} {{ $transaction['id'] }}"><x-icon name="arrow-up-right" size="18" /></button></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div id="history-empty" class="empty-state" hidden><span class="empty-icon"><x-icon name="search" size="28" /></span><h3>{{ __('Belum ada aktivitas yang cocok') }}</h3><p>{{ __('Coba kata kunci lain atau ubah periode riwayat.') }}</p><button type="button" class="button button-neutral" id="reset-filters">{{ __('Reset filter') }}</button></div>
                    <div class="history-footer">
                        <p id="history-count" role="status" aria-live="polite">
                            @if(count($transactions))
                                {{ __('Menampilkan') }} <strong>1–{{ min(6, count($transactions)) }}</strong> {{ __('dari') }} <strong>{{ count($transactions) }}</strong> {{ __('transaksi') }}
                            @else
                                {{ __('Belum ada transaksi') }}
                            @endif
                        </p>
                        <nav id="history-pagination" class="pagination" aria-label="{{ __('Halaman riwayat') }}"><button type="button" class="page-button" id="page-previous" aria-label="{{ __('Halaman sebelumnya') }}" disabled><x-icon name="chevron-left" size="17" /></button><div id="page-numbers"></div><button type="button" class="page-button" id="page-next" aria-label="{{ __('Halaman berikutnya') }}"><x-icon name="chevron-right" size="17" /></button></nav>
                    </div>
                </div>
            </div>
            <p class="history-footnote"><x-icon name="info" size="14" /> {{ __('Poin bertambah dari transaksi selesai dan berkurang saat ditukarkan.') }}</p>
            <noscript><p class="noscript-note">{{ __('Aktifkan JavaScript untuk memakai tab riwayat, pencarian, filter, unduhan, dan detail transaksi.') }}</p></noscript>
        </section>
@endsection
@push('body-end')
    @include('membership.partials.profile-dialogs')
@endpush
