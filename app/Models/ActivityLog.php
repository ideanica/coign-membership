<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['actor_id', 'actor_name', 'actor_email', 'actor_role', 'area', 'action', 'route_name', 'targets', 'occurred_at'];

    public const AREAS = [
        'catalogs' => 'Katalog',
        'discounts' => 'Diskon', 'catalog-categories' => 'Kategori katalog', 'testimonials' => 'Testimoni',
        'services' => 'Layanan', 'statuses' => 'Label & status', 'tracking' => 'Pesanan', 'bills' => 'Bill',
        'settings' => 'Profil & keamanan', 'brand' => 'Pengaturan brand', 'members' => 'Member',
        'points' => 'Poin member', 'point-rules' => 'Poin otomatis', 'administrators' => 'Manajemen admin',
    ];

    public const ACTIONS = [
        'store' => 'Menambahkan data', 'update' => 'Mengubah data',
        'destroy' => 'Menghapus data', 'archive' => 'Mengarsipkan data', 'deactivate' => 'Menonaktifkan data', 'pay' => 'Mencatat pembayaran', 'cancel' => 'Membatalkan bill',
        'access' => 'Mengubah akses admin', 'profile' => 'Mengubah profil', 'password' => 'Mengubah kata sandi',
        'upload' => 'Mengunggah gambar', 'mode' => 'Mengubah mode poin', 'purchase' => 'Mencatat pembelian',
        'restore' => 'Memulihkan katalog', 'publish' => 'Menampilkan katalog', 'hide' => 'Menyembunyikan katalog',
    ];

    public static function visibleAreas(User $user): array
    {
        return $user->isSuperAdmin() ? self::AREAS : array_diff_key(self::AREAS, array_flip(['brand', 'members', 'points', 'point-rules', 'administrators']));
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->whereIn('action', array_keys(self::ACTIONS))->whereIn('area', array_keys(self::visibleAreas($user)));
    }

    protected function casts(): array
    {
        return ['targets' => 'array', 'occurred_at' => 'immutable_datetime'];
    }
}
