@extends('layouts.portal')
@section('title', 'Manajemen admin')
@section('description', 'Kelola akun admin yang membantu operasional katalog, pesanan, dan pembayaran.')
@include('admin.workspace-assets')
@push('styles')<link rel="stylesheet" href="{{ asset('css/admin-preferences.css') }}?v={{ filemtime(public_path('css/admin-preferences.css')) }}">@endpush
@section('actions')<button class="button" type="button" data-dialog-open="administrator-create-dialog">Tambah admin <x-icon name="user" size="17" /></button>@endsection
@section('content')
    <section class="neo-card workspace-mode"><div><p class="section-kicker">AKSES TIM</p><h2>Admin untuk operasional harian.</h2><p>Akun yang ditambahkan di sini selalu berrole admin. Pengelolaan member, poin, dan akun admin tetap menjadi akses khusus superadmin.</p></div><span class="badge">{{ $administrators->total() }} admin</span></section>
    <section class="neo-card administrator-list">
        <div class="workspace-list-heading"><h2>Daftar admin</h2><p>Nonaktifkan akses tanpa menghapus riwayat pekerjaan.</p></div>
        <form class="filter-bar" method="get"><label class="sr-only" for="q">Cari admin</label><input id="q" name="q" value="{{ request('q') }}" maxlength="100" placeholder="Cari nama atau email admin">
            <label class="sr-only" for="access">Status akses</label><select id="access" name="access"><option value="">Semua akses</option><option value="active" @selected(request('access') === 'active')>Aktif</option><option value="inactive" @selected(request('access') === 'inactive')>Nonaktif</option></select>
            <button class="button button-neutral" type="submit">Cari</button>@if(request()->filled('q') || request()->filled('access'))<a class="small-link" href="{{ route('admin.administrators.index') }}">Hapus filter</a>@endif
        </form>
        <div class="portal-table-wrap"><table class="portal-table"><thead><tr><th>ADMIN</th><th>ROLE & AKSES</th><th>DIBUAT</th><th>AKSI</th></tr></thead><tbody>
            @forelse($administrators as $administrator)
                <tr><td><strong>{{ $administrator->name }}</strong><small>{{ $administrator->email }}</small></td><td><strong>Admin</strong><span class="badge workspace-status {{ $administrator->is_active ? 'is-active' : 'is-inactive' }}">{{ $administrator->is_active ? 'Aktif' : 'Nonaktif' }}</span></td><td>{{ $administrator->created_at->locale('id')->translatedFormat('d M Y') }}</td><td>
                    <form method="post" action="{{ route('admin.administrators.access', $administrator) }}" data-confirm="{{ $administrator->is_active ? 'Nonaktifkan akses admin '.$administrator->name.'? Akun ini tidak dapat masuk sampai diaktifkan kembali.' : 'Aktifkan kembali akses admin '.$administrator->name.'?' }}">@csrf @method('PATCH')<input type="hidden" name="is_active" value="{{ $administrator->is_active ? 0 : 1 }}"><button class="row-action {{ $administrator->is_active ? 'danger' : '' }}" type="submit">{{ $administrator->is_active ? 'Nonaktifkan' : 'Aktifkan kembali' }}</button></form>
                </td></tr>
            @empty<tr><td colspan="4" class="portal-empty">{{ request()->filled('q') || request()->filled('access') ? 'Tidak ada admin yang cocok dengan pencarian.' : 'Belum ada admin. Tambahkan admin untuk membantu operasional.' }}</td></tr>@endforelse
        </tbody></table></div><x-pager :paginator="$administrators" />
    </section>
    <dialog id="administrator-create-dialog" class="neo-dialog workspace-dialog" data-managed-dialog aria-labelledby="administrator-create-title" @if($errors->any() && old('_workspace_form') === 'administrator-create') data-open-on-error @endif>
        <div class="dialog-header"><h2 id="administrator-create-title">Tambah admin</h2><button class="detail-button" type="button" data-dialog-close aria-label="Tutup form tambah admin"><x-icon name="close" /></button></div>
        <p class="workspace-dialog-intro">Admin dapat mengelola operasional. Menu member, poin, dan manajemen admin tidak tersedia untuk role ini.</p>
        @if($errors->any() && old('_workspace_form') === 'administrator-create')@include('shared.alerts')@endif
        <form class="admin-form" method="post" action="{{ route('admin.administrators.store') }}">@csrf<input type="hidden" name="_workspace_form" value="administrator-create">
            <x-field name="name" label="Nama admin" required maxlength="100" autocomplete="name" />
            <x-field name="email" label="Email admin" type="email" required maxlength="254" autocomplete="email" />
            <x-field name="password" label="Kata sandi" type="password" required minlength="12" maxlength="255" autocomplete="new-password">Gunakan minimal 12 karakter. Sampaikan kata sandi ini langsung kepada admin.</x-field>
            <p class="form-help">Role akun: <strong>Admin</strong></p>
            <div class="form-actions"><button class="button button-neutral" type="button" data-dialog-close>Batal</button><button class="button" type="submit">Tambah admin <x-icon name="check" size="17" /></button></div>
        </form>
    </dialog>
@endsection
