<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageHelper
{
    /**
     * Process and save multiple images.
     * Stores relative paths (e.g., uploads/items/item_xxx.jpg).
     */
    public static function processImages(array $images = []): array
    {
        return collect($images)->map(function ($image) {
            return self::saveImage($image, 'item_');
        })->toArray();
    }

    /**
     * Save a single image to storage/app/public/uploads/items.
     * Returns relative path (uploads/items/item_xxx.jpg).
     */
    public static function saveImage(UploadedFile $image, string $prefix): string
    {
        $filename = uniqid($prefix) . '.' . $image->getClientOriginalExtension();
        return $image->storeAs('uploads/items', $filename, 'public');
    }

    /**
     * Update images: merge old + new and delete removed ones from storage.
     */
    public static function updateImages(array $newImages, array $oldImages, array $removedImages): array
    {
        $merged = array_merge($oldImages, self::processImages($newImages));

        foreach ($removedImages as $relativePath) {
            if (Storage::disk('public')->exists($relativePath)) {
                Storage::disk('public')->delete($relativePath);
            }
            $merged = array_filter($merged, fn($img) => $img !== $relativePath);
        }

        return array_values($merged);
    }
}
