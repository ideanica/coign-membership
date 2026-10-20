@php
    $ruleMode = $ruleMode ?? 'rule-create';
    $restoreRule = old('_workspace_form', $ruleMode) === $ruleMode;
    $ruleValue = fn ($name, $fallback = '') => $restoreRule ? old($name, $fallback) : $fallback;
@endphp
<form class="admin-form workspace-form" method="post" action="{{ $ruleMode === 'rule-edit' ? route('admin.point-rules.update', $rule->id ?? 0) : route('admin.point-rules.store') }}">
    @csrf<input type="hidden" name="_workspace_form" value="{{ $ruleMode }}">
    @if($ruleMode === 'rule-edit')@method('PUT')<input type="hidden" name="_workspace_id" value="{{ $rule->id }}">@endif
    <div class="form-field"><label for="{{ $ruleMode }}-name">Nama aturan</label><input id="{{ $ruleMode }}-name" name="name" value="{{ $ruleValue('name', $rule->name) }}" required maxlength="100"></div>
    <div class="form-grid">
        <div class="form-field"><label for="{{ $ruleMode }}-minimum">Minimal pembelian (Rp)</label><input id="{{ $ruleMode }}-minimum" name="minimum_amount" type="number" value="{{ $ruleValue('minimum_amount', $rule->minimum_amount) }}" min="1" max="10000000000000" required></div>
        <div class="form-field"><label for="{{ $ruleMode }}-points">Poin yang diberikan</label><input id="{{ $ruleMode }}-points" name="points" type="number" value="{{ $ruleValue('points', $rule->points) }}" min="1" max="100000000" required></div>
    </div>
    <label class="remember-field"><input type="checkbox" name="is_active" value="1" @checked($restoreRule && session()->hasOldInput() ? old('is_active', false) : ($rule->is_active ?? true))> Aturan aktif</label>
    <div class="form-actions"><button class="button button-neutral" type="button" data-dialog-close>Batal</button><button class="button" type="submit">Simpan aturan <x-icon name="check" size="16" /></button></div>
</form>
