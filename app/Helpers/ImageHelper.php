<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;

class ImageHelper
{
    public static function processImages(array $images = []): array
    {
        return collect($images)->map(function ($image) {
            return self::saveImage($image, 'item_');
        })->toArray();
    }

    public static function saveImage(UploadedFile $image, string $prefix): string
    {
        $filename = uniqid($prefix) . '.' . $image->getClientOriginalExtension();
        $image->move(public_path('uploads/items'), $filename);
        return asset('uploads/items/' . $filename);
    }

    public static function updateImages(array $newImages, array $oldImages, array $removedImages): array
    {
        $merged = array_merge($oldImages, self::processImages($newImages));

        foreach ($removedImages as $url) {
            $filePath = public_path(str_replace(asset(''), '', $url));
            if (file_exists($filePath)) @unlink($filePath);
            $merged = array_filter($merged, fn($img) => $img !== $url);
        }

        return array_values($merged);
    }
}
