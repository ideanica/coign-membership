@extends('layouts.portal')
@section('title', 'Member')
@section('description', 'Identitas, akses akun, dan saldo poin pelanggan dalam satu daftar.')
@include('admin.workspace-assets')
@section('actions')<button class="button" type="button" data-dialog-open="member-create-dialog">Tambah member <x-icon name="user" size="16" /></button>@endsection
@section('content')
    @if($temporaryCredentials)
        <div class="portal-alert success" role="status">
            <x-icon name="check" size="18" />
            <div>
                <strong>Kredensial sementara {{ $temporaryCredentials['name'] }}</strong>
                <p>Email: <strong>{{ $temporaryCredentials['email'] }}</strong></p>
                <p>Kata sandi sementara: <strong><code>{{ $temporaryCredentials['password'] }}</code></strong></p>
                <p>Salin sekarang. Kata sandi ini hanya ditampilkan pada kunjungan ini dan member akan diminta membuat kata sandi sendiri setelah login memakai kredensial sementara.</p>
            </div>
        </div>
    @endif
    <section class="neo-card workspace-overview" aria-label="Ringkasan member">
        <div><p>Total member</p><strong>{{ number_format($memberStats['total'], 0, ',', '.') }}</strong></div>
        <div><p>Akun aktif</p><strong>{{ number_format($memberStats['active'], 0, ',', '.') }}</strong></div>
        <div><p>Akun nonaktif</p><strong>{{ number_format($memberStats['inactive'], 0, ',', '.') }}</strong></div>
    </section>
    <section class="neo-card">
        <div class="workspace-list-heading"><h2>Daftar member</h2><p>{{ $members->total() }} member ditemukan</p></div>
        <form class="filter-bar" method="get">
            <x-admin-search label="Cari member" placeholder="Cari nama, email, atau nomor member" />
            <label for="access" class="sr-only">Filter akses</label><select id="access" name="access"><option value="">Semua akses</option><option value="active" @selected(request('access') === 'active')>Aktif</option><option value="inactive" @selected(request('access') === 'inactive')>Nonaktif</option></select>
            @if(request()->filled('access'))<a class="small-link" href="{{ route('admin.members.index') }}">Hapus filter</a>@endif
        </form>
        <div class="portal-table-wrap"><table class="portal-table"><thead><tr><th>MEMBER</th><th>LABEL & AKSES</th><th>SALDO POIN</th><th>AKSI</th></tr></thead><tbody>
            @forelse($members as $member)
                <tr>
                    <td><strong>{{ $member->name }}</strong><small>{{ $member->email }}<br>{{ $member->member_number }}</small></td>
                    <td><strong>{{ $member->member_tier }}</strong><small><span class="badge workspace-status {{ $member->is_active ? 'is-active' : 'is-inactive' }}">{{ $member->is_active ? 'Aktif' : 'Nonaktif' }}</span></small></td>
                    <td><strong class="workspace-balance">{{ number_format(($member->earned_points ?? 0) - ($member->used_points ?? 0), 0, ',', '.') }}</strong><small>+{{ number_format($member->earned_points ?? 0, 0, ',', '.') }} masuk · −{{ number_format($member->used_points ?? 0, 0, ',', '.') }} digunakan</small></td>
                    <td><div class="row-actions">
                        <button class="row-action" type="button" data-dialog-open="member-edit-dialog" data-fill-dialog data-form-action="{{ route('admin.members.update', $member) }}" data-form-values="{{ json_encode(['_workspace_id' => $member->id, 'name' => $member->name, 'email' => $member->email, 'member_tier' => $member->member_tier, 'is_active' => (int) $member->is_active]) }}">Edit</button>
                        <a class="row-action" href="{{ route('admin.points.index', ['member' => $member->id]) }}">Riwayat poin</a>
                        @if($member->is_active)<form method="post" action="{{ route('admin.members.destroy', $member) }}" data-confirm="Nonaktifkan akun {{ $member->name }}? Riwayat dan poin tetap tersimpan.">@csrf @method('DELETE')<button type="submit" class="row-action danger">Nonaktifkan</button></form>@endif
                    </div></td>
                </tr>
            @empty<tr><td colspan="4" class="portal-empty">Belum ada member yang cocok.</td></tr>@endforelse
        </tbody></table></div><x-pager :paginator="$members" />
    </section>
    <dialog id="member-create-dialog" class="neo-dialog workspace-dialog workspace-dialog-wide" data-managed-dialog aria-labelledby="member-create-title" @if($errors->any() && old('_workspace_form') === 'member-create') data-open-on-error @endif>
        <div class="dialog-header"><h2 id="member-create-title">Tambah akun member</h2><button class="detail-button" type="button" data-dialog-close aria-label="Tutup form member"><x-icon name="close" /></button></div>
        <p class="workspace-dialog-intro">Buat identitas dan akses login pelanggan. Saldo akun baru dimulai dari 0 poin.</p>
        @if($errors->any() && old('_workspace_form') === 'member-create')@include('shared.alerts')@endif
        @include('admin.members.member-form', ['member' => $newMember, 'mode' => 'member-create', 'inDialog' => true])
    </dialog>
    <dialog id="member-edit-dialog" class="neo-dialog workspace-dialog workspace-dialog-wide" data-managed-dialog aria-labelledby="member-edit-title" @if($errors->any() && old('_workspace_form') === 'member-edit') data-open-on-error @endif>
        <div class="dialog-header"><h2 id="member-edit-title">Edit akun member</h2><button class="detail-button" type="button" data-dialog-close aria-label="Tutup edit member"><x-icon name="close" /></button></div>
        <p class="workspace-dialog-intro">Perbarui identitas atau akses akun. Riwayat transaksi dan poin tetap tersimpan.</p>
        @if($errors->any() && old('_workspace_form') === 'member-edit')@include('shared.alerts')@endif
        @include('admin.members.member-form', ['member' => $editingMember, 'mode' => 'member-edit', 'inDialog' => true])
    </dialog>
@endsection
