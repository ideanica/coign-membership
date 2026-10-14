<?php

namespace App\Services;

use App\Models\Catalog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CatalogMedia
{
    public function ready(): bool
    {
        $disk = config('catalog.upload_disk');
        $settings = config('filesystems.disks.'.$disk, []);

        return ($settings['driver'] ?? null) === 's3' && filled($settings['key'] ?? null) && filled($settings['secret'] ?? null) && filled($settings['bucket'] ?? null) && filled($settings['endpoint'] ?? null);
    }

    public function upload(UploadedFile $file, int $userId): array
    {
        if (! $this->ready() && ! app()->environment('testing')) {
            throw ValidationException::withMessages(['image' => 'Upload gambar belum diaktifkan. Gunakan tautan gambar HTTPS yang sudah tersedia atau minta pengelola mengatur penyimpanan gambar.']);
        }
        $diskName = config('catalog.upload_disk');
        $disk = Storage::disk($diskName);
        $path = 'catalogs/uploads/'.Str::ulid().'.'.$file->guessExtension();
        $stored = false;
        try {
            $disk->putFileAs('catalogs/uploads', $file, basename($path), ['ContentType' => $file->getMimeType()]);
            $stored = true;
            if (! $disk->exists($path) || $disk->size($path) !== $file->getSize()) {
                throw new \RuntimeException('Upload verification failed.');
            }
            $id = (string) Str::uuid();
            DB::table('catalog_uploads')->insert(['id' => $id, 'user_id' => $userId, 'disk' => $diskName, 'path' => $path, 'mime' => $file->getMimeType(), 'bytes' => $file->getSize(), 'expires_at' => now()->addHour(), 'created_at' => now(), 'updated_at' => now()]);
            $ticket = Crypt::encryptString($id);

            return ['ticket' => $ticket, 'preview_url' => route('admin.catalogs.upload-preview', ['ticket' => $ticket])];
        } catch (\Throwable $exception) {
            if ($exception instanceof ValidationException) {
                throw $exception;
            }
            report($exception);
            if ($stored) {
                try {
                    $disk->delete($path);
                } catch (\Throwable $cleanup) {
                    report($cleanup);
                }
            }
            throw ValidationException::withMessages(['image' => 'Gambar belum berhasil tersimpan. Coba lagi; gambar lama tetap aman.']);
        }
    }

    public function ticket(string $ticket, int $userId, bool $lock = false): object
    {
        try {
            $id = Crypt::decryptString($ticket);
        } catch (\Throwable) {
            throw ValidationException::withMessages(['images' => 'Upload gambar tidak valid. Pilih ulang gambar tersebut.']);
        }
        $query = DB::table('catalog_uploads')->where('id', $id)->where('user_id', $userId)->whereNull('claimed_at')->where('expires_at', '>', now());
        $upload = ($lock ? $query->lockForUpdate() : $query)->first();
        if (! $upload) {
            throw ValidationException::withMessages(['images' => 'Upload gambar sudah digunakan atau kedaluwarsa. Unggah ulang gambar tersebut.']);
        }

        return $upload;
    }

    /** Called inside the catalog transaction so image changes are atomic. */
    public function sync(Catalog $catalog, array $rows, int $primary, int $userId): void
    {
        $keep = [];
        foreach (array_values($rows) as $index => $row) {
            $kinds = array_filter([$row['id'] ?? null, $row['url'] ?? null, $row['ticket'] ?? null]);
            if (count($kinds) !== 1) {
                throw ValidationException::withMessages(['images' => 'Setiap gambar harus memiliki satu sumber: gambar lama, tautan, atau upload baru.']);
            }
            $data = ['alt_text' => $row['alt'] ?? $catalog->name, 'sort_order' => $index, 'is_primary' => $index === $primary];
            if (filled($row['id'] ?? null)) {
                $image = $catalog->images()->find($row['id']);
                if (! $image) {
                    throw ValidationException::withMessages(['images' => 'Gambar lama bukan milik katalog ini.']);
                }
                $image->update($data);
            } elseif (filled($row['ticket'] ?? null)) {
                $upload = $this->ticket($row['ticket'], $userId, true);
                if (! Storage::disk($upload->disk)->exists($upload->path)) {
                    throw ValidationException::withMessages(['images' => 'File upload tidak tersedia. Unggah ulang gambar tersebut.']);
                }
                $image = $catalog->images()->create([...$data, 'disk' => $upload->disk, 'path' => $upload->path]);
                DB::table('catalog_uploads')->where('id', $upload->id)->update(['claimed_at' => now(), 'updated_at' => now()]);
            } else {
                $image = $catalog->images()->create([...$data, 'external_url' => $row['url']]);
            }
            $keep[] = $image->id;
        }
        // Removing a gallery item never deletes unrelated objects from the shared bucket.
        $catalog->images()->whereNotIn('id', $keep)->delete();
    }
}
