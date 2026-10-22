@extends('layouts.portal')
@section('title', 'Kategori katalog')
@section('description', 'Kelompokkan template menurut momen agar pelanggan mudah memilih.')
@include('admin.workspace-assets')
@push('styles')<link rel="stylesheet" href="{{ asset('css/admin-categories.css') }}?v={{ filemtime(public_path('css/admin-categories.css')) }}">@endpush
@push('scripts')<script src="{{ asset('js/admin-categories.js') }}?v={{ filemtime(public_path('js/admin-categories.js')) }}" defer></script>@endpush
@section('actions')<button class="button" type="button" data-dialog-open="category-create-dialog" aria-haspopup="dialog">Tambah kategori <x-icon name="gift" size="16" /></button>@endsection
@section('content')
    <section class="neo-card catalog-category-workspace" aria-labelledby="category-list-title">
        <div class="workspace-list-heading"><h2 id="category-list-title">Daftar kategori</h2><p>{{ $categories->total() }} kategori ditemukan</p></div>
        <form class="filter-bar" method="get">
            <x-admin-search label="Cari kategori" placeholder="Cari nama kategori" />
            <label class="sr-only" for="category-status">Status kategori</label><select id="category-status" name="status"><option value="">Semua status</option><option value="active" @selected(request('status') === 'active')>Aktif</option><option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option></select>
            @if(request()->filled('status'))<a class="small-link" href="{{ route('admin.catalog-categories.index', request()->only('q')) }}">Hapus filter</a>@endif
        </form>
        <ul class="category-list">
            @forelse($categories as $category)
                <li class="category-list-row">
                    <div class="category-list-info"><h3>{{ $category->name }}</h3><p><span>{{ $category->catalogs_count }} template</span><span>Urutan {{ $category->sort_order }}</span></p></div>
                    <span class="badge workspace-status {{ $category->is_active ? 'is-active' : 'is-inactive' }}">{{ $category->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                    <div class="category-row-actions">
                        <button class="button button-neutral" type="button" data-dialog-open="category-edit-dialog" data-fill-dialog data-form-action="{{ route('admin.catalog-categories.update', $category) }}" data-form-values="{{ json_encode(['_category_id' => $category->id, 'name' => $category->name, 'sort_order' => $category->sort_order, 'is_active' => (int) $category->is_active]) }}" aria-haspopup="dialog" aria-label="Edit kategori {{ $category->name }}">Edit <x-icon name="arrow-up-right" size="14" /></button>
                        @if($category->is_active)<form method="post" action="{{ route('admin.catalog-categories.destroy', $category) }}" data-confirm="Nonaktifkan kategori {{ $category->name }}? Template terkait tetap tersimpan dan kategori dapat diaktifkan lagi melalui Edit.">@csrf @method('DELETE')<button class="row-action danger" type="submit">Nonaktifkan</button></form>@endif
                    </div>
                </li>
            @empty
                <li class="portal-empty">{{ request()->anyFilled(['q', 'status']) ? 'Tidak ada kategori yang sesuai. Coba ubah pencarian atau status.' : 'Belum ada kategori. Tambahkan kategori pertama untuk mengelompokkan template.' }}</li>
            @endforelse
        </ul>
        <x-pager :paginator="$categories" />
    </section>
    <dialog id="category-create-dialog" class="neo-dialog workspace-dialog" data-managed-dialog aria-labelledby="category-create-title" @if($errors->any() && old('_workspace_form') === 'category-create') data-open-on-error @endif>
        <div class="dialog-header"><h2 id="category-create-title">Tambah kategori</h2><button class="detail-button" type="button" data-dialog-close aria-label="Tutup tambah kategori"><x-icon name="close" /></button></div>
        <p class="workspace-dialog-intro">Tentukan nama momen, urutan tampil, dan status kategori dalam satu form.</p>
        @include('admin.catalogs.category-form', ['mode' => 'category-create', 'category' => null])
    </dialog>
    <dialog id="category-edit-dialog" class="neo-dialog workspace-dialog" data-managed-dialog aria-labelledby="category-edit-title" @if($errors->any() && old('_workspace_form') === 'category-edit' && $editingCategory) data-open-on-error @endif>
        <div class="dialog-header"><h2 id="category-edit-title">Edit kategori</h2><button class="detail-button" type="button" data-dialog-close aria-label="Tutup edit kategori"><x-icon name="close" /></button></div>
        <p class="workspace-dialog-intro">Perubahan nama dan urutan diterapkan pada kategori yang sama. Template terkait tetap tersimpan.</p>
        @include('admin.catalogs.category-form', ['mode' => 'category-edit', 'category' => $editingCategory])
    </dialog>
@endsection
