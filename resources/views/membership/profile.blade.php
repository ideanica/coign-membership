@extends('layouts.member')
@section('title', 'Profil & poin')
@section('meta-description', 'Profil membership: pantau saldo poin, transaksi, penukaran, dan pendapatan poin dalam satu tempat.')
@push('scripts')<script src="{{ asset('js/membership.js') }}" defer></script>@endpush
@section('content')
<div class="page-heading">
            <div>
                <p class="eyebrow"><span class="tiny-star">✳</span> YOUR MEMBER SPACE</p>
                <h1>Profil & poin<span class="heading-dot">.</span></h1>
                <p class="page-description">Kenali profilmu. Pantau poinmu. Nikmati keuntungannya.</p>
            </div>
            <span class="badge active-badge"><span class="status-dot"></span> Member aktif</span>
        </div>

        <section class="profile-card neo-card" aria-label="Profil dan ringkasan poin member">
            <div class="profile-info">
                <div class="profile-topline"><span class="section-kicker">PROFIL MEMBER</span><span class="member-number">{{ $member['number'] }}</span></div>
                <div class="identity">
                    <div class="avatar-frame"><img src="{{ asset($member['photo']) }}" width="92" height="92" alt="Foto profil ilustrasi {{ $member['name'] }}"><span class="avatar-check"><x-icon name="check" size="13" /></span></div>
                    <div class="identity-copy">
                        <h2>{{ $member['name'] }}</h2>
                        <span class="badge member-badge"><x-icon name="star" size="14" /> {{ $member['tier'] }}</span>
                        <p class="member-email"><x-icon name="mail" size="15" /><span>{{ $member['email'] }}</span></p>
                    </div>
                </div>
                <p class="joined"><x-icon name="calendar" size="15" /> Bergabung sejak {{ $member['joined'] }}</p>
                <div class="point-stats">
                    <div class="point-stat earned-stat">
                        <span class="stat-icon"><x-icon name="arrow-down-left" size="18" /></span>
                        <div><p>Total poin diperoleh</p><strong>+{{ number_format($summary['earned'], 0, ',', '.') }} <span>poin</span></strong></div>
                    </div>
                    <div class="point-stat used-stat">
                        <span class="stat-icon"><x-icon name="arrow-up-right" size="18" /></span>
                        <div><p>Total poin digunakan</p><strong>−{{ number_format($summary['used'], 0, ',', '.') }} <span>poin</span></strong></div>
                    </div>
                </div>
            </div>
            <div class="balance-panel">
                <div class="balance-heading"><span class="section-kicker">POIN TERSEDIA</span><span class="balance-star"><x-icon name="spark" size="36" /></span></div>
                <div class="balance-number">{{ number_format($summary['balance'], 0, ',', '.') }}<span>poin</span></div>
                <p class="balance-description">Hasil aktivitasmu, siap jadi keuntungan baru.</p>
                <button class="button button-neutral balance-button" type="button" data-view-points>Lihat pendapatan poin <x-icon name="arrow-up-right" size="18" /></button>
                <p class="balance-note"><x-icon name="info" size="14" /> Saldo = poin diperoleh − poin digunakan</p>
            </div>
        </section>

        <aside class="points-tip">
            <span class="tip-icon"><x-icon name="gift" size="23" /></span>
            <p><strong>Aktivitas seru, bonusnya poin.</strong><span> Setiap transaksi bisa membawamu ke keuntungan berikutnya.</span></p>
            <button type="button" class="text-button" data-open-guide>Cara kerja poin <x-icon name="arrow-right" size="17" /></button>
        </aside>

        <section id="activity" class="activity-section" aria-labelledby="activity-heading">
            <div class="activity-heading">
                <div><p class="section-kicker">KEEP TRACK OF THE GOOD STUFF</p><h2 id="activity-heading">Semua aktivitasmu</h2><p>Transaksi dan perjalanan poin, tercatat di sini.</p></div>
                <button type="button" class="button button-neutral export-button" id="export-button"><x-icon name="download" size="17" /> Unduh riwayat</button>
            </div>
            <div class="history-card neo-card">
                <div class="history-tabs" role="tablist" aria-label="Jenis riwayat">
                    <button id="tab-transactions" class="history-tab" type="button" role="tab" aria-selected="true" aria-controls="history-panel" data-tab="transactions"><x-icon name="receipt" size="18" /><span>Riwayat transaksi</span><span class="tab-count">{{ count($transactions) }}</span></button>
                    <button id="tab-redemptions" class="history-tab" type="button" role="tab" aria-selected="false" aria-controls="history-panel" tabindex="-1" data-tab="redemptions"><x-icon name="gift" size="18" /><span>Penggunaan poin</span><span class="tab-count">{{ count($redemptions) }}</span></button>
                    <button id="tab-earnings" class="history-tab" type="button" role="tab" aria-selected="false" aria-controls="history-panel" tabindex="-1" data-tab="earnings"><x-icon name="star" size="18" /><span>Pendapatan poin</span><span class="tab-count">{{ count($earnings) }}</span></button>
                </div>
                <div id="history-panel" role="tabpanel" aria-labelledby="tab-transactions" tabindex="0">
                    <div class="history-toolbar">
                        <div class="search-field"><x-icon name="search" size="18" /><label class="sr-only" for="history-search">Cari riwayat berdasarkan nama, nomor, atau tanggal</label><input id="history-search" type="search" placeholder="Cari transaksi atau aktivitas…" autocomplete="off"></div>
                        <div class="history-filters">
                            <div class="select-field"><x-icon name="calendar" size="16" /><label class="sr-only" for="history-period">Filter periode riwayat</label><select id="history-period"><option value="all">Semua periode</option>@foreach($periods as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select><x-icon name="chevron-down" size="15" /></div>
                            <div class="select-field sort-field"><label class="sr-only" for="history-sort">Urutan riwayat</label><select id="history-sort"><option value="newest">Terbaru</option><option value="oldest">Terlama</option></select><x-icon name="chevron-down" size="15" /></div>
                        </div>
                    </div>
                    <p class="table-scroll-hint"><x-icon name="arrow-right" size="13" /> Geser tabel untuk melihat semua kolom.</p>
                    <div class="table-scroller">
                        <table class="history-table">
                            <caption id="table-caption" class="sr-only">Riwayat transaksi member</caption>
                            <thead><tr id="history-columns"><th scope="col">TRANSAKSI</th><th scope="col">TANGGAL</th><th scope="col" class="numeric">TOTAL</th><th scope="col" class="numeric">POIN</th><th scope="col">STATUS</th><th scope="col"><span class="sr-only">Detail</span></th></tr></thead>
                            <tbody id="history-rows">
                                @foreach(array_slice($transactions, 0, 6) as $transaction)
                                    <tr>
                                        <td><div class="activity-cell"><span class="row-icon category-{{ strtolower($transaction['category']) }}"><x-icon name="receipt" size="19" /></span><div><strong>{{ $transaction['title'] }}</strong><span>{{ $transaction['id'] }}</span></div></div></td>
                                        <td><time datetime="{{ $transaction['date'] }}">{{ \Carbon\CarbonImmutable::parse($transaction['date'])->locale('id')->translatedFormat('d M Y') }}</time></td>
                                        <td class="numeric amount-cell">Rp{{ number_format($transaction['amount'], 0, ',', '.') }}</td>
                                        <td class="numeric"><span class="point-change positive">+{{ number_format($transaction['points'], 0, ',', '.') }}</span></td>
                                        <td><span class="badge success-badge"><x-icon name="check" size="12" /> Selesai</span></td>
                                        <td><button class="detail-button" type="button" data-detail="{{ $transaction['id'] }}" aria-label="Lihat detail {{ $transaction['title'] }} {{ $transaction['id'] }}"><x-icon name="arrow-up-right" size="18" /></button></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div id="history-empty" class="empty-state" hidden><span class="empty-icon"><x-icon name="search" size="28" /></span><h3>Belum ada aktivitas yang cocok</h3><p>Coba kata kunci lain atau ubah periode riwayat.</p><button type="button" class="button button-neutral" id="reset-filters">Reset filter</button></div>
                    <div class="history-footer">
                        <p id="history-count" role="status" aria-live="polite">
                            @if(count($transactions))
                                Menampilkan <strong>1–{{ min(6, count($transactions)) }}</strong> dari <strong>{{ count($transactions) }}</strong> transaksi
                            @else
                                Belum ada transaksi
                            @endif
                        </p>
                        <nav id="history-pagination" class="pagination" aria-label="Halaman riwayat"><button type="button" class="page-button" id="page-previous" aria-label="Halaman sebelumnya" disabled><x-icon name="chevron-left" size="17" /></button><div id="page-numbers"></div><button type="button" class="page-button" id="page-next" aria-label="Halaman berikutnya"><x-icon name="chevron-right" size="17" /></button></nav>
                    </div>
                </div>
            </div>
            <p class="history-footnote"><x-icon name="info" size="14" /> Poin bertambah dari transaksi selesai dan berkurang saat ditukarkan.</p>
            <noscript><p class="noscript-note">Aktifkan JavaScript untuk memakai tab riwayat, pencarian, filter, unduhan, dan detail transaksi.</p></noscript>
        </section>
@endsection
@push('body-end')
    @include('membership.partials.profile-dialogs')
@endpush
