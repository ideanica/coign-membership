# Admin dan tracking member

## Akses

Member masuk melalui `/login`, lalu membuka `/tracking` untuk melihat timeline
layanan atau `/bills` untuk membaca tagihannya. Detail tracking dan bill dibatasi
ke pemilik akun; akun nonaktif tidak dapat login atau memakai session lama.

Administrator masuk melalui halaman yang sama dan diarahkan ke `/admin`.
Pendaftaran publik tidak dapat membuat atau meningkatkan role admin.
Buat admin baru dari terminal:

```shell
php artisan admin:create admin@example.com --name="Nama Admin" --generate-password
```

Password baru hanya ditampilkan saat pembuatan, dan disimpan sebagai hash.
Command menolak email yang sudah dipakai agar akun member tidak tertimpa.

## Menu admin

- **Ringkasan:** jumlah member, tracking berjalan, bill belum lunas, dan pembayaran diterima.
- **Member:** tambah/edit profil, label membership, password, dan status aktif.
  Menonaktifkan akun mempertahankan seluruh riwayat.
- **Poin member:** tambah/kurangi poin dengan alasan. Ledger menyimpan admin
  pelaku; pengurangan yang melebihi saldo ditolak. Entri tidak diedit atau dihapus.
- **Layanan:** nama, kode unik, deskripsi, harga, estimasi, serta status aktif.
- **Label & status:** label, palet warna, urutan, dan penanda status akhir.
  Mengarsipkan label/layanan mempertahankan referensi historis.
- **Tracking:** pilih member, layanan, status, target selesai, dan catatan update.
  Perubahan status atau catatan menjadi kejadian baru di timeline.
- **Bill:** quantity × unit price − diskon. Total dihitung server. Bill dapat
  dikaitkan ke satu tracking milik member yang sama.
- **Pengaturan:** nama membership, email pengelola, dan nilai rupiah per poin.
  Perubahan rasio berlaku untuk transaksi berikutnya, tanpa menghitung ulang poin lama.

## Pembayaran dan riwayat

Konfirmasi pembayaran dilakukan manual oleh admin setelah pembayaran diterima.
Tidak ada payment gateway atau tombol member untuk mengubah bill menjadi lunas.
Konfirmasi yang berulang hanya menghasilkan satu transaksi dan satu pendapatan
poin. Bill lunas dibekukan; bill yang dibatalkan tidak dapat dibayar. Tidak ada
alur refund otomatis pada versi ini.

Timeline menyimpan label dan warna pada saat kejadian. Mengubah nama label tidak
mengubah kejadian lama. Pemilik tracking dan bill tidak dapat dipindahkan saat edit.

## Data contoh dan pengujian

`php artisan db:seed --class=PortalDemoSeeder --force` membuat layanan, tracking,
dan bill belum lunas hanya untuk akun `dummy@coignitive.test`. Seeder tidak
mengubah saldo poin yang sudah ada. Harus tersedia akun dummy dan admin aktif.

Jalankan `php artisan test` untuk memeriksa role, isolasi data member, CRUD,
audit poin, saldo, status tracking, perhitungan bill, konfirmasi pembayaran
berulang, dan penerapan pengaturan. Tes otomatis memakai SQLite dalam memori.
