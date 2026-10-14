@extends('layouts.portal')
@section('title', 'Poin member')
@section('description', 'Tambah atau kurangi poin dengan alasan yang tercatat pada ledger.')
@section('actions')<a class="button button-neutral" href="{{ route('admin.point-rules.index') }}">Pembelian & poin otomatis <x-icon name="spark" size="16" /></a>@endsection
@section('content')
    <form class="neo-card portal-card admin-form" method="post" action="{{ route('admin.points.store') }}">
        @csrf
        <div class="form-grid"><div class="form-field"><label for="user_id">Member</label><select name="user_id" id="user_id" required><option value="">Pilih member aktif</option>@foreach($members->where('is_active', true) as $member)<option value="{{ $member->id }}" @selected(old('user_id', request('member')) == $member->id)>{{ $member->name }} · saldo {{ number_format(($member->earned_points ?? 0) - ($member->used_points ?? 0), 0, ',', '.') }} poin</option>@endforeach</select></div><div class="form-field"><label for="type">Jenis perubahan</label><select name="type" id="type"><option value="earned" @selected(old('type') === 'earned')>Tambah poin (+)</option><option value="redeemed" @selected(old('type') === 'redeemed')>Kurangi poin (−)</option></select></div></div>
        <x-field name="points" label="Jumlah poin" type="number" :value="old('points')" min="1" max="100000000" required />
        <div class="form-field"><label for="reason">Alasan perubahan</label><textarea id="reason" name="reason" rows="3" maxlength="2000" required placeholder="Contoh: bonus loyalitas atau koreksi penukaran">{{ old('reason') }}</textarea><small>Alasan dan nama admin tersimpan. Pengurangan tidak boleh melebihi saldo.</small></div>
        <div class="form-actions"><button class="button" type="submit">Catat perubahan poin</button></div>
    </form>
    <section class="neo-card" style="margin-top:28px"><form class="filter-bar" method="get"><label class="sr-only" for="member">Filter riwayat member</label><select name="member" id="member"><option value="">Semua member</option>@foreach($members as $member)<option value="{{ $member->id }}" @selected(request('member') == $member->id)>{{ $member->name }}</option>@endforeach</select><button class="button button-neutral">Tampilkan</button></form>
        <div class="portal-table-wrap"><table class="portal-table"><thead><tr><th>MEMBER / AKTIVITAS</th><th>POIN</th><th>ALASAN</th><th>ADMIN</th><th>TANGGAL</th></tr></thead><tbody>
            @forelse($entries as $entry)
                <tr><td><strong>{{ $entry->user->name }}</strong><small>{{ $entry->title }}<br>{{ $entry->reference }}</small></td><td><span class="point-change {{ $entry->type === 'earned' ? 'positive' : 'negative' }}">{{ $entry->type === 'earned' ? '+' : '−' }}{{ number_format($entry->points, 0, ',', '.') }}</span></td><td>{{ $entry->description ?: '—' }}</td><td>{{ $entry->actor?->name ?? 'Sistem / transaksi' }}</td><td>{{ $entry->occurred_at->format('d M Y H:i') }}</td></tr>
            @empty
                <tr><td colspan="5" class="portal-empty">Belum ada riwayat poin.</td></tr>
            @endforelse
        </tbody></table></div><x-pager :paginator="$entries" />
    </section>
@endsection
