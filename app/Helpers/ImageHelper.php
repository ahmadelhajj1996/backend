<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageHelper
{
    /**
     * Upload image
     */
    public static function upload(
        UploadedFile $file,
        string $folder = 'uploads',
        string $disk = 'public'
    ): string {

        $extension = strtolower($file->getClientOriginalExtension());

        $filename = Str::uuid() . '.' . $extension;

        return $file->storeAs($folder, $filename, $disk);
    }

    /**
     * Delete image
     */
    public static function delete(
        ?string $path,
        string $disk = 'public'
    ): bool {

        if (!$path) {
            return false;
        }

        if (Storage::disk($disk)->exists($path)) {
            return Storage::disk($disk)->delete($path);
        }

        return false;
    }

    /**
     * Get public image URL
     */
    public static function url(
        ?string $path,
        string $disk = 'public'
    ): ?string {

        if (!$path) {
            return null;
        }

        // remove accidental leading slash
        $path = ltrim($path, '/');

        return Storage::disk($disk)->url($path);
    }
}