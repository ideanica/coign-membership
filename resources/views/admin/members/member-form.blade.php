@php
    $mode = $mode ?? ($member->exists ? 'member-edit' : 'member-create');
    $prefix = $mode.'-';
    $restore = old('_workspace_form', $mode) === $mode;
    $fieldValue = fn ($name, $fallback = '') => $restore ? old($name, $fallback) : $fallback;
@endphp
<form class="neo-card portal-card admin-form workspace-form" method="post" action="{{ $mode === 'member-edit' ? route('admin.members.update', $member->id ?? 0) : route('admin.members.store') }}">
    @csrf
    <input type="hidden" name="_workspace_form" value="{{ $mode }}">
    @if($mode === 'member-edit') @method('PUT')<input type="hidden" name="_workspace_id" value="{{ $member->id }}"> @endif
    <fieldset class="workspace-fieldset"><legend>IDENTITAS MEMBER</legend>
        <div class="form-grid">
            <div class="form-field"><label for="{{ $prefix }}name">Nama lengkap</label><input id="{{ $prefix }}name" name="name" value="{{ $fieldValue('name', $member->name) }}" required maxlength="100" autocomplete="name"></div>
            <div class="form-field"><label for="{{ $prefix }}email">Email</label><input id="{{ $prefix }}email" name="email" type="email" value="{{ $fieldValue('email', $member->email) }}" required maxlength="254" autocomplete="email"></div>
        </div>
        <div class="form-grid">
            <div class="form-field"><label for="{{ $prefix }}member_tier">Label member</label><input id="{{ $prefix }}member_tier" name="member_tier" value="{{ $fieldValue('member_tier', $member->member_tier ?? 'Member') }}" required maxlength="64"></div>
            <div class="form-field"><label for="{{ $prefix }}is_active">Akses akun</label><select id="{{ $prefix }}is_active" name="is_active"><option value="1" @selected($fieldValue('is_active', $member->is_active ?? true))>Aktif</option><option value="0" @selected(!$fieldValue('is_active', $member->is_active ?? true))>Nonaktif</option></select></div>
        </div>
    </fieldset>
    <fieldset class="workspace-fieldset"><legend>AKSES LOGIN</legend>
        @if($mode === 'member-create')
            <p class="form-help">Sistem akan membuat kata sandi sementara secara otomatis. Setelah akun dibuat, salin kredensial yang tampil satu kali. Member dapat masuk dengan email + kata sandi sementara atau memakai Google dengan alamat email yang sama.</p>
        @else
            <p class="form-help">Kata sandi dikelola langsung oleh member dari halaman Keamanan Akun. Jika email diubah, tautan Google yang lama akan dilepas agar akun Google sebelumnya tidak tetap memiliki akses.</p>
        @endif
    </fieldset>
    <div class="form-actions">@if($inDialog ?? false)<button class="button button-neutral" type="button" data-dialog-close>Batal</button>@else<a class="button button-neutral" href="{{ route('admin.members.index') }}">Batal</a>@endif<button class="button" type="submit">Simpan member <x-icon name="check" size="16" /></button></div>
</form>
