@extends('layouts.portal')
@section('title', 'Tracking pesanan')
@section('description', 'Kelola pesanan pelanggan, status layanan, dan akses tracking publik.')
@section('actions')<a class="button" href="{{ route('admin.tracking.create') }}">Buat pesanan <x-icon name="calendar" size="16" /></a>@endsection
@section('content')
    <section class="neo-card"><form class="filter-bar" method="get"><label class="sr-only" for="q">Cari tracking</label><input name="q" id="q" value="{{ request('q') }}" placeholder="Cari judul, nomor tracking, atau member…"><label class="sr-only" for="status">Status tracking</label><select id="status" name="status"><option value="">Semua status</option>@foreach($statuses as $status)<option value="{{ $status->id }}" @selected(request('status') == $status->id)>{{ $status->label }}</option>@endforeach</select><button class="button button-neutral">Filter</button></form>
        <div class="portal-table-wrap"><table class="portal-table"><thead><tr><th>TRACKING</th><th>MEMBER</th><th>LAYANAN</th><th>STATUS</th><th>TARGET</th><th>AKSI</th></tr></thead><tbody>
            @forelse($trackings as $tracking)
                <tr><td><strong>{{ $tracking->title }}</strong><small>{{ $tracking->reference }}</small></td><td>{{ $tracking->user?->name ?? 'Pelanggan tanpa akun' }}</td><td>{{ $tracking->service?->name ?? 'Layanan khusus' }}</td><td><span class="badge status-badge" style="--status-color:{{ $tracking->status->color }}">{{ $tracking->status->label }}</span></td><td>{{ $tracking->due_date?->format('d M Y') ?? '—' }}</td><td><a class="row-action" href="{{ route('admin.tracking.edit', $tracking) }}">Kelola tracking</a></td></tr>
            @empty
                <tr><td colspan="6" class="portal-empty">Belum ada tracking yang cocok.</td></tr>
            @endforelse
        </tbody></table></div><x-pager :paginator="$trackings" />
    </section>
@endsection
