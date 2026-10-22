@extends('layouts.portal')
@section('title', 'Log aktivitas')
@section('description', 'Riwayat aktivitas tim: siapa yang melakukan aksi, di bagian mana, dan kapan.')
@include('admin.workspace-assets')
@push('styles')<link rel="stylesheet" href="{{ asset('css/activity-logs.css') }}?v={{ filemtime(public_path('css/activity-logs.css')) }}">@endpush
@section('content')
    <section class="neo-card activity-intro"><div><p class="section-kicker">RIWAYAT TIM</p><h2>Aktivitas tercatat, pekerjaan mudah ditelusuri.</h2><p>Menampilkan aksi perubahan data yang berhasil: tambah, edit, hapus, arsip, pemulihan, dan perubahan status. Pencatatan dimulai sejak fitur ini diaktifkan. {{ auth()->user()->isSuperAdmin() ? 'Kamu dapat melihat aktivitas seluruh area.' : 'Aktivitas member, poin, brand, dan manajemen admin hanya dapat dilihat superadmin.' }} Waktu ditampilkan dalam zona {{ config('app.timezone') }}.</p></div><span class="badge">{{ $logs->total() }} aktivitas</span></section>
    <section class="neo-card activity-list" aria-label="Daftar aktivitas">
        <form method="get" action="{{ route('admin.activity-logs.index') }}">
            <div class="filter-bar activity-search-row"><x-admin-search label="Cari log aktivitas" placeholder="Cari nama atau email tim"><button class="button button-neutral" type="button" data-dialog-open="activity-filter-dialog" aria-label="Buka filter log" aria-haspopup="dialog"><x-icon name="filter" />@if(request()->anyFilled(['actor', 'area', 'action', 'from', 'to']))<span class="activity-filter-dot" aria-label="Filter aktif"></span>@endif</button></x-admin-search></div>
            <dialog id="activity-filter-dialog" class="neo-dialog workspace-dialog" data-managed-dialog aria-labelledby="activity-filter-title" @if($errors->any()) data-open-on-error @endif>
                <div class="dialog-header"><h2 id="activity-filter-title">Filter log aktivitas</h2><button class="detail-button" type="button" data-dialog-close aria-label="Tutup filter"><x-icon name="close" /></button></div>
                <div class="activity-filter-fields">
                    <div class="form-field"><label for="actor">Nama / email pelaku</label><input id="actor" name="actor" maxlength="100" value="{{ request('actor') }}" placeholder="Nama atau email anggota tim"></div>
                    <div class="form-field"><label for="area">Area</label><select id="area" name="area"><option value="">Semua area</option>@foreach($areas as $key => $label)<option value="{{ $key }}" @selected(request('area') === $key)>{{ $label }}</option>@endforeach</select></div>
                    <div class="form-field"><label for="action">Aktivitas</label><select id="action" name="action"><option value="">Semua aktivitas</option>@foreach($actions as $key => $label)<option value="{{ $key }}" @selected(request('action') === $key)>{{ $label }}</option>@endforeach</select></div>
                    <div class="form-field"><label for="from">Dari tanggal</label><input id="from" name="from" type="date" value="{{ request('from') }}"></div>
                    <div class="form-field"><label for="to">Sampai tanggal</label><input id="to" name="to" type="date" value="{{ request('to') }}"></div>
                </div>
                <div class="form-actions"><a class="button button-neutral" href="{{ route('admin.activity-logs.index') }}">Hapus filter</a><button class="button" type="submit">Terapkan filter</button></div>
            </dialog>
        </form>
        <ol class="activity-entries">
            @forelse($logs as $log)
                <li class="activity-entry"><div class="activity-person"><strong>{{ $log->actor_name }}</strong><span>{{ $log->actor_email }}</span><span class="badge">{{ $log->actor_role }}</span></div>
                    <div class="activity-description"><strong>{{ $actions[$log->action] ?? 'Melakukan aksi' }}</strong><p>{{ $areas[$log->area] ?? $log->area }}</p><time datetime="{{ $log->occurred_at->toIso8601String() }}">{{ $log->occurred_at->timezone(config('app.timezone'))->format('d M Y, H:i:s') }}</time>
                        <details><summary>Detail aktivitas</summary><dl><div><dt>Halaman / aksi</dt><dd>{{ $log->route_name }}</dd></div>@foreach($log->targets ?? [] as $key => $value)<div><dt>ID {{ $key }}</dt><dd>{{ $value }}</dd></div>@endforeach<div><dt>Nomor log</dt><dd>#{{ $log->id }}</dd></div></dl></details>
                    </div></li>
            @empty<li class="portal-empty">Belum ada aktivitas yang sesuai. Coba ubah filter atau lakukan aktivitas di panel admin.</li>@endforelse
        </ol>
        <x-pager :paginator="$logs" />
    </section>
@endsection
