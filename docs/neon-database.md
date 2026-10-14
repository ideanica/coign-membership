# Database Neon

Koneksi lokal dikonfigurasi melalui `.env`, yang sudah diabaikan oleh Git.
Gunakan `DB_CONNECTION=pgsql` dan simpan connection URI di `DB_URL`.
Jangan menaruh kredensial di Blade, JavaScript, atau file konfigurasi yang dilacak Git.

Pengaturan koneksi:

```dotenv
DB_CONNECTION=pgsql
DB_URL="postgresql://USER:PASSWORD@HOST/neondb?sslmode=require&channel_binding=require"
DB_SSLMODE=require
DB_CHANNEL_BINDING=require
DB_CONNECT_TIMEOUT=10
```

`App\Database\NeonPostgresConnector` menambahkan `channel_binding` dan
`connect_timeout` ke DSN PDO. Konektor standar Laravel tidak meneruskan kedua
opsi tersebut. PHP harus menyediakan `pdo_pgsql` dan libpq yang mendukung channel binding.

Setelah mengubah `.env`, jalankan:

```shell
php artisan config:clear
php artisan neon:check --schema
```

Perintah `neon:check` memeriksa koneksi dan SSL. Opsi `--schema` menampilkan nama
tabel, kolom, dan tipe data. Perintah ini tidak mengubah tabel atau membaca isi
record member. Kredensial disamarkan pada pesan kegagalan koneksi.

Session dan cache lokal menggunakan file, sedangkan queue menggunakan `sync`,
sehingga pemeriksaan halaman tidak memerlukan tabel infrastruktur Laravel di Neon.

## Data membership

Migrasi membuat tabel `users`, `membership_transactions`, dan `point_entries`,
beserta tabel bawaan Laravel. Daftar melalui `/register`, lalu masuk melalui
`/login`. Akun baru mendapatkan nomor member dan saldo 0; database tidak diisi
akun atau transaksi contoh. `/` hanya menampilkan profil akun yang login.

Saldo berasal dari ledger: total entri `earned` dikurangi total entri `redeemed`.
Pendapatan poin bisa berasal dari transaksi atau entri bonus tersendiri.
Filter periode mengikuti tanggal data yang tersedia.

Catat transaksi selesai melalui `App\Services\MembershipLedger::recordPurchase()`
dan penukaran melalui `redeem()`. Kedua operasi berjalan dalam transaksi database
dan mengunci baris member. Referensi transaksi harus unik; penukaran yang melebihi
saldo ditolak. Tidak ada endpoint publik untuk mengubah saldo sendiri.

Contoh penggunaan dari integrasi backend atau `php artisan tinker`:

```php
$member = App\Models\User::where('email', 'member@example.com')->firstOrFail();
$ledger = app(App\Services\MembershipLedger::class);
$ledger->recordPurchase($member, [
    'reference' => 'INV-UNIK-001',
    'title' => 'Workshop',
    'category' => 'Workshop',
    'amount' => 350000,
    'payment' => 'QRIS',
    'occurred_at' => now(),
]);
```

Rasio awal adalah 1 poin per kelipatan Rp1.000. Admin dapat mengubahnya melalui
`/admin/settings`; nilai disimpan di `site_settings`. `config/membership.php`
menjadi nilai fallback.
Foto profil menggunakan `users.photo_path`, dengan avatar lokal sebagai fallback.

## Laporan SSL

`neon:check` melaporkan mode SSL klien dari konfigurasi koneksi serta informasi
`pg_stat_ssl` secara terpisah. Statistik PostgreSQL di belakang proxy tidak
menyatakan status TLS antara aplikasi dan endpoint Neon. Koneksi klien tetap
menggunakan `sslmode=require` dan `channel_binding=require`.

## Pengujian

Jalankan `php artisan test`. Pengujian memakai SQLite dalam memori, terpisah
dari Neon, dan mencakup registrasi, login/logout, isolasi data member, pendapatan
poin, saldo, transaksi duplikat, serta penukaran tanpa saldo cukup.

Panduan pengelolaan tersedia di [Admin dan tracking member](admin-and-tracking.md).
