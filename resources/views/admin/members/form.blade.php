@extends('layouts.portal')
@section('title', $member->exists ? 'Edit akun member' : 'Buat akun member')
@section('description', 'Admin membuat akun pelanggan, menentukan akses, dan mengatur kata sandi login.')
@section('content')
    <form class="neo-card portal-card admin-form" method="post" action="{{ $member->exists ? route('admin.members.update', $member) : route('admin.members.store') }}">
        @csrf
        @if($member->exists) @method('PUT') @endif
        <div class="form-grid"><x-field name="name" label="Nama lengkap" :value="$member->name" required maxlength="100" /><x-field name="email" label="Email" type="email" :value="$member->email" required maxlength="254" /></div>
        <div class="form-grid"><x-field name="member_tier" label="Label member" :value="$member->member_tier ?? 'Member'" required maxlength="64" /><div class="form-field"><label for="is_active">Akses akun</label><select id="is_active" name="is_active"><option value="1" @selected(old('is_active', $member->is_active ?? true))>Aktif</option><option value="0" @selected(!old('is_active', $member->is_active ?? true))>Nonaktif</option></select></div></div>
        <div class="form-grid"><x-field name="password" label="Kata sandi" type="password" minlength="8" autocomplete="new-password" :required="!$member->exists">{{ $member->exists ? 'Kosongkan untuk mempertahankan kata sandi lama.' : 'Minimal 8 karakter. Berikan email dan kata sandi ini kepada pelanggan.' }}</x-field><x-field name="password_confirmation" label="Konfirmasi kata sandi" type="password" autocomplete="new-password" :required="!$member->exists" /></div>
        @if(!$member->exists)<div class="form-help">Email ini menjadi identitas login. Akun baru dimulai dengan saldo 0 poin; poin diberikan setelah pembelian dicatat.</div>@endif
        <div class="form-actions"><button class="button" type="submit">Simpan member <x-icon name="check" size="16" /></button><a class="button button-neutral" href="{{ route('admin.members.index') }}">Batal</a></div>
    </form>
@endsection
