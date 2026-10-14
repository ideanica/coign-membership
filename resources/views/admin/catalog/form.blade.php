@extends('layouts.portal')
@section('title', ($record->exists ? 'Edit ' : 'Tambah ').($kind === 'services' ? 'layanan' : 'label tracking'))
@section('description', $kind === 'services' ? 'Harga dan estimasi ini menjadi acuan operasional layanan.' : 'Warna label menggunakan palet neobrutalism yang sama dengan halaman member.')
@section('content')
    <form class="neo-card portal-card admin-form" method="post" action="{{ route('admin.'.$kind.'.'.($record->exists ? 'update' : 'store'), $record->exists ? $record : []) }}">
        @csrf
        @if($record->exists) @method('PUT') @endif
        @if($kind === 'services')
            <div class="form-grid"><x-field name="name" label="Nama layanan" :value="$record->name" required maxlength="255" /><x-field name="code" label="Kode layanan" :value="$record->code" required maxlength="64">Gunakan huruf, angka, tanda hubung, atau underscore.</x-field></div>
            <div class="form-field"><label for="description">Deskripsi layanan</label><textarea id="description" name="description" rows="4" maxlength="4000">{{ old('description', $record->description) }}</textarea></div>
            <div class="form-grid"><x-field name="price" label="Harga (Rp)" type="number" :value="$record->price ?? 0" min="0" max="1000000000" required /><x-field name="delivery_days" label="Estimasi pengerjaan (hari)" type="number" :value="$record->delivery_days ?? 1" min="0" max="3650" required /></div>
        @else
            <x-field name="label" label="Nama label/status" :value="$record->label" required maxlength="100" />
            <div class="form-grid"><div class="form-field"><label for="color">Warna label</label><select id="color" name="color">@foreach(\App\Models\TrackingStatus::COLORS as $color => $label)<option value="{{ $color }}" @selected(old('color', $record->color ?? '#5294ff') === $color)>{{ $label }}</option>@endforeach</select></div><x-field name="sort_order" label="Urutan tampilan" type="number" :value="$record->sort_order ?? 0" min="0" max="9999" required /></div>
            <div class="form-field"><label for="is_final">Status akhir</label><select name="is_final" id="is_final"><option value="0" @selected(!old('is_final', $record->is_final ?? false))>Tidak · tracking masih berjalan</option><option value="1" @selected(old('is_final', $record->is_final ?? false))>Ya · tracking ditutup</option></select><small>Contoh: Selesai atau Dibatalkan. Penanda akhir diterapkan saat tracking disimpan; mengubah label tidak menutup semua tracking secara otomatis.</small></div>
        @endif
        <div class="form-field"><label for="is_active">Ketersediaan</label><select name="is_active" id="is_active"><option value="1" @selected(old('is_active', $record->is_active ?? true))>Aktif</option><option value="0" @selected(!old('is_active', $record->is_active ?? true))>Diarsipkan</option></select></div>
        <div class="form-actions"><button class="button" type="submit">Simpan <x-icon name="check" size="16" /></button><a class="button button-neutral" href="{{ route('admin.'.$kind.'.index') }}">Batal</a></div>
    </form>
@endsection
