@extends('layouts.portal')
@section('title', 'Arsip katalog')
@section('description', 'Katalog yang diarsipkan tidak tampil di publik. Pulihkan untuk mengedit dan mengaktifkannya kembali.')
@section('actions')<a class="button button-neutral" href="{{ route('admin.catalogs.index') }}">Kembali ke katalog</a>@endsection
@section('content')
<section class="neo-card">
    <form class="filter-bar" method="get"><label class="sr-only" for="q">Cari katalog arsip</label><input id="q" name="q" value="{{ request('q') }}" maxlength="100" placeholder="Cari katalog arsip"><button class="button button-neutral" type="submit">Cari</button>@if(request()->filled('q'))<a class="small-link" href="{{ route('admin.catalogs.archive') }}">Hapus pencarian</a>@endif</form>
    <div class="portal-table-wrap"><table class="portal-table"><thead><tr><th>KATALOG</th><th>KATEGORI</th><th>DIARSIPKAN</th><th>AKSI</th></tr></thead><tbody>
        @forelse($catalogs as $catalog)<tr><td><strong>{{ $catalog->name }}</strong><small>{{ $catalog->slug }}</small></td><td>{{ $catalog->categories->pluck('name')->join(', ') }}</td><td>{{ $catalog->deleted_at->format('d M Y, H:i') }}</td><td><form method="post" action="{{ route('admin.catalogs.restore', $catalog) }}" data-confirm="Pulihkan katalog ini? Katalog akan tetap disembunyikan sampai kamu mengaktifkannya.">@csrf @method('PATCH')<button class="row-action" type="submit">Pulihkan katalog</button></form></td></tr>
        @empty<tr><td colspan="4" class="portal-empty">{{ request()->filled('q') ? 'Tidak ada katalog arsip yang sesuai pencarian.' : 'Belum ada katalog yang diarsipkan.' }}</td></tr>@endforelse
    </tbody></table></div><x-pager :paginator="$catalogs" />
</section>
@endsection
