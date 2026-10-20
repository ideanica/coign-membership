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
    <fieldset class="workspace-fieldset"><legend>{{ $mode === 'member-edit' ? 'GANTI KATA SANDI (OPSIONAL)' : 'KATA SANDI LOGIN' }}</legend>
        <div class="form-grid">
            <div class="form-field"><label for="{{ $prefix }}password">Kata sandi</label><input id="{{ $prefix }}password" name="password" type="password" minlength="8" autocomplete="new-password" @required($mode === 'member-create')><small>{{ $mode === 'member-edit' ? 'Kosongkan kedua kolom untuk mempertahankan kata sandi.' : 'Minimal 8 karakter. Berikan akun ini kepada pelanggan.' }}</small></div>
            <div class="form-field"><label for="{{ $prefix }}password_confirmation">Konfirmasi kata sandi</label><input id="{{ $prefix }}password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" @required($mode === 'member-create')></div>
        </div>
    </fieldset>
    <div class="form-actions">@if($inDialog ?? false)<button class="button button-neutral" type="button" data-dialog-close>Batal</button>@else<a class="button button-neutral" href="{{ route('admin.members.index') }}">Batal</a>@endif<button class="button" type="submit">Simpan member <x-icon name="check" size="16" /></button></div>
</form>
