@extends('layouts.portal')
@section('title', $kind === 'services' ? 'Layanan' : 'Label & status')
@section('description', $kind === 'services' ? 'Atur katalog layanan, harga, dan estimasi pengerjaan.' : 'Atur label, warna, urutan, dan status akhir tracking.')
@section('actions')<a class="button" href="{{ route('admin.'.$kind.'.create') }}">{{ $kind === 'services' ? 'Tambah layanan' : 'Tambah label' }} <x-icon name="arrow-right" size="16" /></a>@endsection
@section('content')
    <section class="neo-card">
        @if($kind === 'services')
            <form method="get" class="filter-bar"><x-admin-search label="Cari layanan" placeholder="Cari nama layanan" /></form>
        @endif
        <div class="portal-table-wrap"><table class="portal-table"><thead><tr><th>{{ $kind === 'services' ? 'LAYANAN' : 'LABEL TRACKING' }}</th><th>{{ $kind === 'services' ? 'HARGA' : 'URUTAN' }}</th><th>{{ $kind === 'services' ? 'ESTIMASI' : 'STATUS AKHIR' }}</th><th>AKTIF</th><th>AKSI</th></tr></thead><tbody>
            @forelse($records as $record)
                <tr><td>
                    @if($kind === 'services')<strong>{{ $record->name }}</strong><small>{{ $record->code }}</small>@else<span class="badge status-badge" style="--status-color:{{ $record->color }}">{{ $record->label }}</span>@endif
                </td><td>{{ $kind === 'services' ? 'Rp'.number_format($record->price, 0, ',', '.') : $record->sort_order }}</td><td>{{ $kind === 'services' ? $record->delivery_days.' hari' : ($record->is_final ? 'Ya · tracking ditutup' : 'Tidak · masih berjalan') }}</td><td>{{ $record->is_active ? 'Aktif' : 'Diarsipkan' }}</td><td><div class="row-actions"><a class="row-action" href="{{ route('admin.'.$kind.'.edit', $record) }}">Edit</a>
                    @if($record->is_active)
                        <form method="post" action="{{ route('admin.'.$kind.'.destroy', $record) }}" data-confirm="Arsipkan data ini? Riwayat tracking dan bill yang menggunakannya tetap tersimpan.">@csrf @method('DELETE')<button type="submit" class="row-action danger">Arsipkan</button></form>
                    @endif
                </div></td></tr>
            @empty
                <tr><td colspan="5" class="portal-empty">Belum ada data. Gunakan tombol tambah untuk membuatnya.</td></tr>
            @endforelse
        </tbody></table></div><x-pager :paginator="$records" />
    </section>
@endsection
