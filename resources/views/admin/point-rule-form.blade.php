@php
    $ruleMode = $ruleMode ?? 'rule-create';
    $restoreRule = old('_workspace_form', $ruleMode) === $ruleMode;
    $ruleValue = fn ($name, $fallback = '') => $restoreRule ? old($name, $fallback) : $fallback;
@endphp
<form class="admin-form workspace-form" data-tier-rule-form method="post" action="{{ $ruleMode === 'rule-edit' ? route('admin.point-rules.update', $rule->id ?? 0) : route('admin.point-rules.store') }}">
    @csrf<input type="hidden" name="_workspace_form" value="{{ $ruleMode }}">
    @if($ruleMode === 'rule-edit')@method('PUT')<input type="hidden" name="_workspace_id" value="{{ $rule->id }}">@endif
    <div class="form-field"><label for="{{ $ruleMode }}-name">Nama aturan</label><input id="{{ $ruleMode }}-name" name="name" value="{{ $ruleValue('name', $rule->name) }}" required maxlength="100"></div>
    <div class="form-grid">
        <div class="form-field"><label for="{{ $ruleMode }}-minimum">Minimal pembelian (Rp)</label><input id="{{ $ruleMode }}-minimum" name="minimum_amount" type="number" value="{{ $ruleValue('minimum_amount', $rule->minimum_amount) }}" min="1" max="10000000000000" required></div>
        <div class="form-field"><label for="{{ $ruleMode }}-maximum">Maksimal pembelian (Rp)</label><input id="{{ $ruleMode }}-maximum" name="maximum_amount" type="number" value="{{ $ruleValue('maximum_amount', $rule->maximum_amount) }}" min="1" max="10000000000000" placeholder="Tanpa batas atas"><small>Kosongkan jika tidak ada batas atas. Nilai minimum dan maksimum ikut dihitung.</small></div>
    </div>
    <div class="form-field"><label for="{{ $ruleMode }}-reward-type">Cara memberi poin</label><select id="{{ $ruleMode }}-reward-type" name="reward_type" data-reward-type><option value="fixed" @selected($ruleValue('reward_type', $rule->reward_type ?? 'fixed') === 'fixed')>Poin tetap per transaksi</option><option value="multiple" @selected($ruleValue('reward_type', $rule->reward_type) === 'multiple')>Poin per kelipatan belanja</option></select></div>
    <div class="form-field"><label for="{{ $ruleMode }}-points">Jumlah poin hadiah</label><input id="{{ $ruleMode }}-points" name="points" type="number" value="{{ $ruleValue('points', $rule->points) }}" min="1" max="100000000" required><small>Poin tetap: diberikan satu kali. Kelipatan: diberikan untuk setiap kelipatan penuh dari total pembelian, bukan hanya selisih di atas minimum.</small></div>
    <div class="form-grid" data-multiple-fields>
        <div class="form-field"><label for="{{ $ruleMode }}-step">Per kelipatan belanja (Rp)</label><input id="{{ $ruleMode }}-step" name="step_amount" type="number" value="{{ $ruleValue('step_amount', $rule->step_amount) }}" min="1" max="10000000000000"><small>Contoh: 500 poin setiap Rp10.000. Sisa kelipatan dibulatkan ke bawah.</small></div>
        <div class="form-field"><label for="{{ $ruleMode }}-cap">Batas poin per transaksi (opsional)</label><input id="{{ $ruleMode }}-cap" name="maximum_points" type="number" value="{{ $ruleValue('maximum_points', $rule->maximum_points) }}" min="1" max="100000000" placeholder="Maksimal 100.000.000 poin"><small>Isi untuk membatasi hadiah kelipatan. Jika kosong, batas sistem 100.000.000 poin berlaku.</small></div>
    </div>
    <p class="form-help">Rentang aktif tidak boleh tumpang tindih. Contoh: Rp50.000-Rp69.999 mendapat 5.000 poin, lalu Rp70.000 ke atas mendapat 7.000 poin. Belanja Rp80.000 hanya memakai aturan kedua.</p>
    <label class="remember-field"><input type="checkbox" name="is_active" value="1" @checked($restoreRule && session()->hasOldInput() ? old('is_active', false) : ($rule->is_active ?? true))> Aturan aktif</label>
    <div class="form-actions"><button class="button button-neutral" type="button" data-dialog-close>Batal</button><button class="button" type="submit">Simpan aturan <x-icon name="check" size="16" /></button></div>
</form>
