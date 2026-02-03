<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ImageUploadService
{
    /**
     * Upload image directly to public folder
     * 
     * @param UploadedFile $file
     * @param string $directory (relative to public/uploads)
     * @param string|null $oldPath
     * @return string
     */
    public function upload(UploadedFile $file, string $directory = 'products', ?string $oldPath = null): string
    {
        // 1. Delete old image if exists
        $this->delete($oldPath);

        // 2. Prepare directory
        $destinationPath = public_path("uploads/{$directory}");

        // Ensure directory exists
        if (!File::exists($destinationPath)) {
            File::makeDirectory($destinationPath, 0755, true);
        }

        // 3. Generate unique filename
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

        // 4. Move file to public directory
        $file->move($destinationPath, $filename);

        // Return relative path for database
        return "uploads/{$directory}/{$filename}";
    }

    /**
     * Delete image from public folder
     * 
     * @param string|null $path
     * @return void
     */
    public function delete(?string $path): void
    {
        if ($path) {
            $absolutePath = public_path($path);
            if (File::exists($absolutePath)) {
                File::delete($absolutePath);
            }
        }
    }
}
