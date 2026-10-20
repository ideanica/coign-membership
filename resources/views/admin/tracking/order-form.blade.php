        <form class="neo-card portal-card admin-form {{ ($inDialog ?? false) ? 'order-dialog-form' : '' }}" method="post" action="{{ $tracking->exists ? route('admin.tracking.update', $tracking) : route('admin.tracking.store') }}">
            @csrf
            @if($tracking->exists)
                @method('PUT')
                @if(!($inDialog ?? false))
                <p class="tracking-ref">{{ $tracking->reference }}</p>
                <div class="form-help"><strong>Kode pesanan publik</strong><p style="margin:10px 0;overflow-wrap:anywhere">{{ $tracking->public_code }}</p><button type="button" class="button button-neutral" data-copy-reference="{{ $tracking->public_code }}">Salin</button><span class="sr-only" data-copy-status role="status"></span><p style="margin-top:12px">Pelanggan memasukkan kode ini di <a class="small-link" href="{{ route('public.tracking.index') }}">tracking publik</a>. Kode hanya berfungsi jika akses publik diaktifkan.</p></div>
                <div class="form-field"><label>Member</label><p>{{ $tracking->user?->name ?? 'Pelanggan tanpa akun' }}</p><small>Pemilik tracking tetap sama untuk menjaga riwayat.</small></div>
                @endif
            @else
                <div class="form-field"><label for="user_id">Member (opsional)</label><select name="user_id" id="user_id"><option value="">Pelanggan tanpa akun</option>@foreach($members as $member)<option value="{{ $member->id }}" @selected(old('user_id') == $member->id)>{{ $member->name }} · {{ $member->email }}</option>@endforeach</select><small>Pilih akun jika pelanggan sudah menjadi member.</small></div>
            @endif
            @if($tracking->exists)
                <input type="hidden" name="service_id" value="{{ $tracking->service_id }}">
            @endif
            <x-field name="title" label="Judul pesanan" :value="$tracking->title" required maxlength="255" />
            <div class="form-field"><label for="description">Deskripsi pekerjaan</label><textarea name="description" id="description" rows="3" maxlength="4000">{{ old('description', $tracking->description) }}</textarea></div>
            <div class="form-grid"><div class="form-field"><label for="tracking_status_id">Status saat ini</label><select id="tracking_status_id" name="tracking_status_id" required>@foreach($statuses as $status)<option value="{{ $status->id }}" @selected(old('tracking_status_id', $tracking->tracking_status_id) == $status->id)>{{ $status->label }}</option>@endforeach</select></div><x-field name="due_date" label="Target selesai" type="date" :value="$tracking->due_date?->format('Y-m-d')" /></div>
            <div class="form-field"><label for="note">Update untuk member</label><textarea name="note" id="note" rows="3" maxlength="2000" placeholder="Tulis perkembangan terbaru…">{{ old('note') }}</textarea><small>Catatan detail hanya terlihat oleh member dan admin. Tracking publik menampilkan riwayat status tanpa catatan ini.</small></div>
            <input type="hidden" name="public_enabled" value="0"><label class="remember-field"><input type="checkbox" name="public_enabled" value="1" @checked(old('public_enabled', $tracking->public_enabled))> Aktifkan tracking publik untuk kode pesanan ini</label><div class="form-help">Siapa pun yang memiliki kode dapat melihat judul layanan, status, target selesai, dan riwayat status. Nama, email, bill, deskripsi, dan catatan member tidak ditampilkan.</div>
            @if(!$tracking->exists)
                <section class="order-billing" data-order-billing><h2>Rincian harga & bill</h2><p class="muted">Bill diterbitkan otomatis saat pesanan disimpan. Pastikan harga dan pelanggan benar karena rincian ini tidak bisa diedit setelah terbit.</p>
                    <div class="form-grid" style="margin-top:18px"><x-field name="customer_name" label="Nama pelanggan tanpa akun" maxlength="255" placeholder="Isi jika tidak memilih member" /><x-field name="customer_email" label="Email pelanggan (opsional)" type="email" maxlength="255" /></div>
                    <div class="form-grid" style="margin-top:18px"><x-field name="quantity" label="Jumlah" type="number" value="1" required min="1" max="10000" /><x-field name="unit_price" label="Harga yang disepakati (Rp)" type="number" required min="1" max="1000000000">Isi harga pesanan secara manual sesuai kesepakatan dengan pelanggan.</x-field></div>
                    <div class="form-grid" style="margin-top:18px"><x-field name="discount" label="Diskon (Rp)" type="number" value="0" required min="0" max="10000000000000" /><x-field name="bill_due_date" label="Jatuh tempo pembayaran (opsional)" type="date" /></div>
                    <div class="form-help" style="margin-top:18px">Total bill: <strong data-order-total>Rp0</strong><br>Poin member diberikan setelah pembayaran dikonfirmasi, bukan saat pesanan dibuat.</div>
                </section>
            @elseif($tracking->bill && !($inDialog ?? false))
                <div class="form-help">Bill pesanan sudah diterbitkan dan rincian harganya terkunci. <a class="small-link" href="{{ route('admin.tracking.show', $tracking).'#bill' }}">Lihat bill {{ $tracking->bill->reference }}</a></div>
            @endif
            <div class="form-actions">@if($inDialog ?? false)<button class="button button-neutral" type="button" data-order-close>Batal</button>@endif<button class="button" type="submit">{{ $tracking->exists ? 'Simpan perkembangan' : 'Buat pesanan & bill' }}</button>@if(!$tracking->exists)<a class="button button-neutral" href="{{ route('admin.tracking.index') }}">Kembali</a>@endif</div>
        </form>
