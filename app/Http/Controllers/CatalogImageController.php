<?php

namespace App\Http\Controllers;

use App\Models\CatalogImage;
use App\Services\CatalogMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CatalogImageController extends Controller
{
    public function upload(Request $request, CatalogMedia $media)
    {
        $values = $request->validate(['image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096']]);

        return response()->json($media->upload($values['image'], $request->user()->id));
    }

    public function preview(Request $request, CatalogMedia $media)
    {
        $values = $request->validate(['ticket' => ['required', 'string', 'max:8192']]);
        $upload = $media->ticket($values['ticket'], $request->user()->id);

        return Storage::disk($upload->disk)->response($upload->path, null, ['Content-Type' => $upload->mime, 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function show(Request $request, CatalogImage $image)
    {
        abort_unless($image->catalog && ($image->catalog->is_active || $request->user()?->is_admin), 404);
        if ($image->external_url) {
            return redirect()->away($image->external_url);
        }
        abort_unless($image->disk === config('catalog.upload_disk') && filled($image->path), 404);
        try {
            return Storage::disk($image->disk)->response($image->path, null, ['Cache-Control' => 'public, max-age=3600', 'X-Content-Type-Options' => 'nosniff']);
        } catch (\Throwable $exception) {
            report($exception);
            abort(404);
        }
    }
}
