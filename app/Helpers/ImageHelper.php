<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageHelper
{
    /**
     * Upload image and return stored path
     */
    public static function upload(
        UploadedFile $file,
        string $folder = 'uploads',
        string $disk = 'public'
    ): string {
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

        $path = $file->storeAs($folder, $filename, $disk);

        return $path; // stored in DB
    }

    /**
     * Delete image safely
     */
    public static function delete(?string $path, string $disk = 'public'): void
    {
        if ($path && Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->delete($path);
        }
    }
 
    
    public static function url(?string $path, string $disk = 'public'): ?string
    {
        if (!$path) return null;

        return Storage::disk($disk)->url($path);
    }
}