<?php

use App\Support\ImageOptimizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

/**
 * Width and height of a stored file on the fake public disk.
 *
 * @return array{0: int, 1: int}
 */
function storedImageSize(string $path): array
{
    [$width, $height] = getimagesize(Storage::disk('public')->path($path));

    return [$width, $height];
}

it('scales a large photo down to the maximum dimension and stores it as webp', function () {
    $path = ImageOptimizer::store(UploadedFile::fake()->image('kamera.jpg', 4000, 3000), 'news');

    expect($path)->toStartWith('news/')->toEndWith('.webp')
        ->and(storedImageSize($path))->toBe([1920, 1440]);
});

it('keeps the size of a photo that is already small enough', function () {
    $path = ImageOptimizer::store(UploadedFile::fake()->image('kecil.png', 800, 600), 'programs');

    expect($path)->toEndWith('.webp')
        ->and(storedImageSize($path))->toBe([800, 600]);
});

it('scales portrait photos by their height', function () {
    $path = ImageOptimizer::store(UploadedFile::fake()->image('potret.jpg', 1500, 3000), 'facilities');

    expect(storedImageSize($path))->toBe([960, 1920]);
});

it('stores the original file when it cannot be decoded as an image', function () {
    $file = UploadedFile::fake()->createWithContent('rusak.jpg', 'bukan gambar');

    $path = ImageOptimizer::store($file, 'activities');

    expect($path)->toStartWith('activities/')->not->toEndWith('.webp');
    Storage::disk('public')->assertExists($path);
});
