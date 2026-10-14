@extends('layouts.portal')
@section('title', 'Member')
@section('description', 'Kelola profil, label membership, dan akses akun member.')
@section('actions')<a class="button" href="{{ route('admin.members.create') }}">Buat akun member <x-icon name="user" size="16" /></a>@endsection
@section('content')
    <section class="neo-card"><form class="filter-bar" method="get"><label for="q" class="sr-only">Cari member</label><input id="q" name="q" value="{{ request('q') }}" placeholder="Cari nama, email, atau nomor member…"><button class="button button-neutral" type="submit">Cari</button><a class="small-link" href="{{ route('admin.members.index') }}">Reset</a></form>
        <div class="portal-table-wrap"><table class="portal-table"><thead><tr><th>MEMBER</th><th>LABEL</th><th>SALDO POIN</th><th>AKSES</th><th>AKSI</th></tr></thead><tbody>
            @forelse($members as $member)
                <tr><td><strong>{{ $member->name }}</strong><small>{{ $member->email }}<br>{{ $member->member_number }}</small></td><td><span class="badge member-badge">{{ $member->member_tier }}</span></td><td>{{ number_format(($member->earned_points ?? 0) - ($member->used_points ?? 0), 0, ',', '.') }}</td><td><span class="badge" style="background:{{ $member->is_active ? '#ddfbe9' : '#ffe0e0' }}">{{ $member->is_active ? 'Aktif' : 'Nonaktif' }}</span></td><td><div class="row-actions"><a class="row-action" href="{{ route('admin.members.edit', $member) }}">Edit</a><a class="row-action" href="{{ route('admin.points.index', ['member' => $member->id]) }}">Poin</a>
                    @if($member->is_active)
                        <form method="post" action="{{ route('admin.members.destroy', $member) }}" data-confirm="Nonaktifkan akun {{ $member->name }}? Riwayat member tetap tersimpan.">@csrf @method('DELETE')<button type="submit" class="row-action danger">Nonaktifkan</button></form>
                    @endif
                </div></td></tr>
            @empty
                <tr><td colspan="5" class="portal-empty">Belum ada member yang cocok.</td></tr>
            @endforelse
        </tbody></table></div><x-pager :paginator="$members" />
    </section>
@endsection
