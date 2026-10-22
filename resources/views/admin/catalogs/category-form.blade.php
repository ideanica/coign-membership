@php
    $isCategoryRetry = $errors->any() && old('_workspace_form') === $mode;
    $categoryValue = function ($field, $default = '') use ($isCategoryRetry) {
        $value = $isCategoryRetry ? old($field, $default) : $default;
        return is_scalar($value) || is_null($value) ? $value : $default;
    };
@endphp
<form class="admin-form workspace-form" method="post" action="{{ $mode === 'category-create' ? route('admin.catalog-categories.store') : ($category ? route('admin.catalog-categories.update', $category) : '#') }}">
    @csrf
    @if($mode === 'category-edit')@method('PUT')<input type="hidden" name="_category_id" value="{{ $category?->id }}">@endif
    <input type="hidden" name="_workspace_form" value="{{ $mode }}">
    @if($isCategoryRetry)@include('shared.alerts')@endif
    <div class="form-field"><label for="{{ $mode }}-name">Nama kategori</label><input id="{{ $mode }}-name" name="name" value="{{ $categoryValue('name', $category?->name ?? '') }}" required maxlength="100" placeholder="Contoh: Baby shower"><small>Nama kategori membantu pelanggan menemukan template sesuai momennya.</small></div>
    <div class="form-grid">
        <div class="form-field"><label for="{{ $mode }}-order">Urutan tampil</label><input id="{{ $mode }}-order" name="sort_order" type="number" min="0" max="10000" required value="{{ $categoryValue('sort_order', $category?->sort_order ?? $nextSortOrder) }}"><small>Angka lebih kecil tampil lebih awal.</small></div>
        <div class="form-field"><label for="{{ $mode }}-status">Status kategori</label><select id="{{ $mode }}-status" name="is_active"><option value="1" @selected((string) $categoryValue('is_active', (int) ($category?->is_active ?? true)) === '1')>Aktif</option><option value="0" @selected((string) $categoryValue('is_active', (int) ($category?->is_active ?? true)) === '0')>Nonaktif</option></select><small>Menonaktifkan kategori tidak menghapus template terkait.</small></div>
    </div>
    <div class="form-actions"><button class="button button-neutral" type="button" data-dialog-close>Batal</button><button class="button" type="submit">{{ $mode === 'category-create' ? 'Tambah kategori' : 'Simpan perubahan' }} <x-icon name="check" size="16" /></button></div>
</form>
