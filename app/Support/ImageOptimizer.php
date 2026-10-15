<?php

namespace App\Support;

use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stores uploaded photos on the "public" disk in a web-friendly size.
 *
 * Phone and camera photos are often 4000-6000px wide and several MB, which makes the landing page slow.
 * Uploads are rotated upright (EXIF orientation), scaled down to at most MAX_DIMENSION on the long side
 * and re-encoded as WebP. When GD is missing or the file can't be decoded (e.g. an animated WebP),
 * the original file is stored unchanged so an upload never fails because of optimization.
 */
class ImageOptimizer
{
    public const MAX_DIMENSION = 1920;

    public const WEBP_QUALITY = 80;

    /**
     * Store the photo in $directory on the public disk and return its path.
     */
    public static function store(UploadedFile $file, string $directory): string
    {
        $image = self::decode($file);

        if ($image === null) {
            return $file->store($directory, 'public');
        }

        $image = self::scaleDown($image);

        ob_start();
        $encoded = imagewebp($image, null, self::WEBP_QUALITY);
        $webp = ob_get_clean();

        if (! $encoded || $webp === false || $webp === '') {
            return $file->store($directory, 'public');
        }

        $path = $directory.'/'.Str::random(40).'.webp';
        Storage::disk('public')->put($path, $webp);

        return $path;
    }

    private static function decode(UploadedFile $file): ?GdImage
    {
        if (! extension_loaded('gd')) {
            return null;
        }

        $realPath = $file->getRealPath();
        $info = $realPath ? @getimagesize($realPath) : false;

        if ($info === false) {
            return null;
        }

        $image = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($realPath),
            IMAGETYPE_PNG => @imagecreatefrompng($realPath),
            IMAGETYPE_WEBP => @imagecreatefromwebp($realPath),
            default => false,
        };

        if (! $image instanceof GdImage) {
            return null;
        }

        if ($info[2] === IMAGETYPE_JPEG) {
            $image = self::applyExifOrientation($image, $realPath);
        }

        imagepalettetotruecolor($image);
        imagealphablending($image, false);
        imagesavealpha($image, true);

        return $image;
    }

    private static function applyExifOrientation(GdImage $image, string $path): GdImage
    {
        $orientation = function_exists('exif_read_data') ? (@exif_read_data($path)['Orientation'] ?? 1) : 1;

        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };

        return $rotated instanceof GdImage ? $rotated : $image;
    }

    private static function scaleDown(GdImage $image): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $longestSide = max($width, $height);

        if ($longestSide <= self::MAX_DIMENSION) {
            return $image;
        }

        $ratio = self::MAX_DIMENSION / $longestSide;
        $scaled = imagescale($image, (int) round($width * $ratio), (int) round($height * $ratio), IMG_BICUBIC);

        if (! $scaled instanceof GdImage) {
            return $image;
        }

        imagealphablending($scaled, false);
        imagesavealpha($scaled, true);

        return $scaled;
    }
}
