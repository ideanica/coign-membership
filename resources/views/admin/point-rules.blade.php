@extends('layouts.portal')
@section('title', 'Poin otomatis')
@section('description', 'Atur cara perolehan poin dan batas hadiah pembelian. Perubahan berlaku untuk transaksi berikutnya.')
@include('admin.workspace-assets')
@push('styles')<link rel="stylesheet" href="{{ asset('css/commerce.css') }}">@endpush
@push('scripts')<script src="{{ asset('js/automatic-points.js') }}?v={{ filemtime(public_path('js/automatic-points.js')) }}" defer></script>@endpush
@section('actions')
    <button class="button button-neutral" type="button" data-dialog-open="point-purchase-dialog">Catat pembelian <x-icon name="receipt" size="16" /></button>
    <button class="button" type="button" data-dialog-open="rule-create-dialog">Tambah aturan <x-icon name="star" size="16" /></button>
@endsection
@section('content')
    <section class="neo-card workspace-mode" aria-labelledby="points-mode-title">
        <div><span class="section-kicker">MODE PEROLEHAN POIN</span><h2 id="points-mode-title">{{ ($settings['points_mode'] ?? 'ratio') === 'tiers' ? 'Aturan bertingkat' : 'Rasio pembelian' }}</h2>
            <p>@if(($settings['points_mode'] ?? 'ratio') === 'tiers')Satu pembelian hanya memakai satu rentang aktif yang sesuai. Hadiah antaraturan tidak dijumlahkan. Pembelian di luar semua rentang mendapat 0 poin.@else Setiap kelipatan Rp{{ number_format($settings['rupiah_per_point'] ?? 1000, 0, ',', '.') }} dari pembayaran selesai menghasilkan 1 poin.@endif</p>
        </div>
        <button class="button button-neutral" type="button" data-dialog-open="points-mode-dialog">Ubah mode</button>
    </section>
    <section class="neo-card">
        <div class="workspace-list-heading"><h2>Aturan pembelian bertingkat</h2><p>{{ $rules->count() }} aturan · diurutkan dari batas terkecil</p></div>
        <div class="portal-table-wrap"><table class="portal-table"><thead><tr><th>ATURAN</th><th>RENTANG PEMBELIAN</th><th>HADIAH POIN</th><th>STATUS</th><th>AKSI</th></tr></thead><tbody>
            @forelse($rules as $rule)
                <tr><td><strong>{{ $rule->name }}</strong></td><td><strong>Rp{{ number_format($rule->minimum_amount, 0, ',', '.') }}</strong><small>sampai {{ $rule->maximum_amount === null ? 'tanpa batas atas' : 'Rp'.number_format($rule->maximum_amount, 0, ',', '.') }}</small></td><td><strong>+{{ number_format($rule->points, 0, ',', '.') }}</strong><small>{{ $rule->reward_type === 'multiple' ? 'per Rp'.number_format($rule->step_amount, 0, ',', '.') : 'tetap per transaksi' }}</small>@if($rule->reward_type === 'multiple')<small>Batas {{ number_format($rule->maximum_points ?? \App\Models\PointRule::MAX_POINTS, 0, ',', '.') }} poin</small>@endif</td><td><span class="badge workspace-status {{ $rule->is_active ? 'is-active' : 'is-inactive' }}">{{ $rule->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                    <td><div class="row-actions">
                        <button class="row-action" type="button" data-dialog-open="rule-edit-dialog" data-fill-dialog data-form-action="{{ route('admin.point-rules.update', $rule) }}" data-form-values="{{ json_encode(['_workspace_id' => $rule->id, 'name' => $rule->name, 'minimum_amount' => $rule->minimum_amount, 'maximum_amount' => $rule->maximum_amount, 'reward_type' => $rule->reward_type, 'step_amount' => $rule->step_amount, 'maximum_points' => $rule->maximum_points, 'points' => $rule->points, 'is_active' => $rule->is_active]) }}">Edit</button>
                        <form method="post" action="{{ route('admin.point-rules.destroy', $rule) }}" data-confirm="Hapus aturan {{ $rule->name }}? Riwayat poin yang sudah masuk tidak berubah.">@csrf @method('DELETE')<button class="row-action danger" type="submit">Hapus</button></form>
                    </div></td>
                </tr>
            @empty<tr><td colspan="5" class="portal-empty">Belum ada aturan. Tambahkan rentang pembelian dan cara perolehan poin melalui tombol Tambah aturan.</td></tr>@endforelse
        </tbody></table></div>
    </section>
    <dialog id="points-mode-dialog" class="neo-dialog workspace-dialog" data-managed-dialog aria-labelledby="points-mode-dialog-title" @if($errors->any() && old('_workspace_form') === 'points-mode') data-open-on-error @endif>
        <div class="dialog-header"><h2 id="points-mode-dialog-title">Mode perolehan poin</h2><button class="detail-button" type="button" data-dialog-close aria-label="Tutup mode poin"><x-icon name="close" /></button></div>
        <p class="workspace-dialog-intro">Mode berlaku untuk pembelian manual dan bill yang ditandai lunas. Poin yang sudah tercatat tidak dihitung ulang.</p>
        @if($errors->any() && old('_workspace_form') === 'points-mode')@include('shared.alerts')@endif
        <form class="admin-form" method="post" action="{{ route('admin.point-rules.mode') }}">
            @csrf<input type="hidden" name="_workspace_form" value="points-mode">
            <div class="form-field"><label for="points_mode">Cara menghitung poin</label><select id="points_mode" name="points_mode"><option value="ratio" @selected(old('points_mode', $settings['points_mode'] ?? 'ratio') === 'ratio')>Rasio pembelian</option><option value="tiers" @selected(old('points_mode', $settings['points_mode'] ?? 'ratio') === 'tiers')>Aturan bertingkat</option></select><small>Aturan bertingkat memakai satu rentang pembelian yang sesuai. Tidak ada hadiah jika nominal berada di luar seluruh rentang aktif.</small></div>
            <a class="small-link" href="{{ route('admin.settings.edit') }}">Atur nilai rupiah per poin</a>
            <div class="form-actions"><button class="button button-neutral" type="button" data-dialog-close>Batal</button><button class="button" type="submit">Simpan mode</button></div>
        </form>
    </dialog>
    <dialog id="point-purchase-dialog" class="neo-dialog workspace-dialog workspace-dialog-wide" data-managed-dialog aria-labelledby="point-purchase-title" @if($errors->any() && old('_workspace_form') === 'point-purchase') data-open-on-error @endif>
        <div class="dialog-header"><h2 id="point-purchase-title">Catat pembelian selesai</h2><button class="detail-button" type="button" data-dialog-close aria-label="Tutup pencatatan pembelian"><x-icon name="close" /></button></div>
        <p class="workspace-dialog-intro">Catat pembayaran yang sudah diterima dan belum memiliki bill. Poin dihitung otomatis berdasarkan mode tersimpan.</p>
        @if($errors->any() && old('_workspace_form') === 'point-purchase')@include('shared.alerts')@endif
        @include('admin.point-purchase-form')
    </dialog>
    <dialog id="rule-create-dialog" class="neo-dialog workspace-dialog" data-managed-dialog aria-labelledby="rule-create-title" @if($errors->any() && old('_workspace_form') === 'rule-create') data-open-on-error @endif>
        <div class="dialog-header"><h2 id="rule-create-title">Tambah aturan poin</h2><button class="detail-button" type="button" data-dialog-close aria-label="Tutup aturan poin"><x-icon name="close" /></button></div>
        <p class="workspace-dialog-intro">Atur rentang pembelian dan pilih hadiah tetap atau per kelipatan. Rentang aturan aktif tidak boleh bertabrakan.</p>
        @if($errors->any() && old('_workspace_form') === 'rule-create')@include('shared.alerts')@endif
        @include('admin.point-rule-form', ['rule' => $newRule, 'ruleMode' => 'rule-create'])
    </dialog>
    <dialog id="rule-edit-dialog" class="neo-dialog workspace-dialog" data-managed-dialog aria-labelledby="rule-edit-title" @if($errors->any() && old('_workspace_form') === 'rule-edit') data-open-on-error @endif>
        <div class="dialog-header"><h2 id="rule-edit-title">Edit aturan poin</h2><button class="detail-button" type="button" data-dialog-close aria-label="Tutup edit aturan"><x-icon name="close" /></button></div>
        <p class="workspace-dialog-intro">Perubahan hadiah hanya berlaku untuk transaksi berikutnya.</p>
        @if($errors->any() && old('_workspace_form') === 'rule-edit')@include('shared.alerts')@endif
        @include('admin.point-rule-form', ['rule' => $editingRule, 'ruleMode' => 'rule-edit'])
    </dialog>
    @php
        $pointRuleData = $rules->where('is_active', true)->map(fn ($rule) => [
            'name' => $rule->name, 'minimum' => $rule->minimum_amount, 'maximum' => $rule->maximum_amount,
            'type' => $rule->reward_type, 'step' => $rule->step_amount,
            'cap' => $rule->maximum_points ?? \App\Models\PointRule::MAX_POINTS, 'points' => $rule->points,
        ])->values();
    @endphp
    <script id="point-rule-data" type="application/json">@json($pointRuleData)</script>
@endsection
