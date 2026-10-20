@extends('layouts.portal')
@section('title', 'Poin member')
@section('description', 'Pantau saldo dan riwayat poin. Penyesuaian dicatat beserta alasan dan pengelolanya.')
@include('admin.workspace-assets')
@section('actions')
    <a class="button button-neutral" href="{{ route('admin.point-rules.index') }}">Poin otomatis <x-icon name="spark" size="16" /></a>
    <button class="button" type="button" data-dialog-open="point-adjustment-dialog">Sesuaikan poin <x-icon name="star" size="16" /></button>
@endsection
@section('content')
    @php($selectedMember = $members->firstWhere('id', request()->integer('member')))
    <section class="neo-card workspace-overview" aria-label="Ringkasan poin">
        <div><p>Poin masuk</p><strong>+{{ number_format($summary['earned'], 0, ',', '.') }}</strong><small>{{ $selectedMember?->name ?? 'Semua member' }}</small></div>
        <div><p>Poin digunakan</p><strong>−{{ number_format($summary['used'], 0, ',', '.') }}</strong></div>
        <div><p>Saldo tercatat</p><strong>{{ number_format($summary['earned'] - $summary['used'], 0, ',', '.') }}</strong></div>
    </section>
    <section class="neo-card">
        <div class="workspace-list-heading"><h2>Riwayat poin</h2><p>{{ $entries->total() }} aktivitas ditemukan</p></div>
        <form class="filter-bar" method="get">
            <label class="sr-only" for="q">Cari aktivitas poin</label><input id="q" name="q" value="{{ request('q') }}" placeholder="Cari aktivitas, alasan, atau nomor referensi…">
            <label class="sr-only" for="member">Filter member</label><select name="member" id="member"><option value="">Semua member</option>@foreach($members as $member)<option value="{{ $member->id }}" @selected(request('member') == $member->id)>{{ $member->name }}</option>@endforeach</select>
            <label class="sr-only" for="entry_type">Jenis poin</label><select name="type" id="entry_type"><option value="">Semua jenis</option><option value="earned" @selected(request('type') === 'earned')>Poin masuk</option><option value="redeemed" @selected(request('type') === 'redeemed')>Poin digunakan</option></select>
            <button class="button button-neutral" type="submit">Tampilkan</button>
            @if(request()->filled('q') || request()->filled('member') || request()->filled('type'))<a class="small-link" href="{{ route('admin.points.index') }}">Reset</a>@endif
        </form>
        <div class="portal-table-wrap"><table class="portal-table"><thead><tr><th>MEMBER / AKTIVITAS</th><th>POIN</th><th>PENCATATAN</th><th>AKSI</th></tr></thead><tbody>
            @forelse($entries as $entry)
                <tr><td><strong>{{ $entry->user->name }}</strong><small>{{ $entry->title }}<br>{{ $entry->reference }}</small></td><td><span class="point-change {{ $entry->type === 'earned' ? 'positive' : 'negative' }}">{{ $entry->type === 'earned' ? '+' : '−' }}{{ number_format($entry->points, 0, ',', '.') }}</span></td><td>{{ $entry->occurred_at->locale('id')->translatedFormat('d M Y, H:i') }}<small>{{ $entry->actor?->name ?? 'Sistem / transaksi' }}</small></td><td><button class="row-action" type="button" data-dialog-open="point-entry-{{ $entry->id }}">Detail</button></td></tr>
            @empty<tr><td colspan="4" class="portal-empty">Belum ada riwayat yang sesuai filter.</td></tr>@endforelse
        </tbody></table></div><x-pager :paginator="$entries" />
    </section>
    <dialog id="point-adjustment-dialog" class="neo-dialog workspace-dialog workspace-dialog-wide" data-managed-dialog aria-labelledby="point-adjustment-title" @if($errors->any()) data-open-on-error @endif>
        <div class="dialog-header"><h2 id="point-adjustment-title">Penyesuaian poin</h2><button class="detail-button" type="button" data-dialog-close aria-label="Tutup penyesuaian poin"><x-icon name="close" /></button></div>
        <p class="workspace-dialog-intro">Gunakan untuk bonus atau koreksi saldo. Untuk pesanan yang memiliki bill, konfirmasi pembayaran dari detail pesanan.</p>
        @if($errors->any())@include('shared.alerts')@endif
        @include('admin.point-adjustment-form')
    </dialog>
    @foreach($entries as $entry)
        <dialog id="point-entry-{{ $entry->id }}" class="neo-dialog workspace-dialog" data-managed-dialog aria-labelledby="point-entry-title-{{ $entry->id }}">
            <div class="dialog-header"><h2 id="point-entry-title-{{ $entry->id }}">Detail aktivitas poin</h2><button class="detail-button" type="button" data-dialog-close aria-label="Tutup detail poin"><x-icon name="close" /></button></div>
            <dl class="workspace-detail-list">
                <div><dt>Member</dt><dd>{{ $entry->user->name }}</dd></div>
                <div><dt>Aktivitas</dt><dd>{{ $entry->title }}</dd></div>
                <div><dt>Perubahan</dt><dd>{{ $entry->type === 'earned' ? '+' : '−' }}{{ number_format($entry->points, 0, ',', '.') }} poin</dd></div>
                <div><dt>Alasan</dt><dd>{{ $entry->description ?: 'Tidak ada keterangan tambahan.' }}</dd></div>
                <div><dt>Referensi</dt><dd>{{ $entry->reference }}</dd></div>
                <div><dt>Dicatat oleh</dt><dd>{{ $entry->actor?->name ?? 'Sistem / transaksi' }}</dd></div>
                <div><dt>Tanggal</dt><dd>{{ $entry->occurred_at->locale('id')->translatedFormat('d F Y, H:i') }}</dd></div>
            </dl>
            <div class="form-actions"><button class="button button-neutral" type="button" data-dialog-close>Tutup</button></div>
        </dialog>
    @endforeach
@endsection
