<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;

/**
 * Model with a single photo stored on the "public" disk in the `image` column.
 *
 * The old file is removed when the photo is replaced or cleared and when the model is deleted,
 * so the storage folder never keeps orphaned uploads.
 */
trait HasPublicImage
{
    public static function bootHasPublicImage(): void
    {
        static::updated(function (self $model): void {
            $previousImage = $model->getOriginal('image');

            if ($model->wasChanged('image') && $previousImage) {
                Storage::disk('public')->delete($previousImage);
            }
        });

        static::deleted(function (self $model): void {
            if ($model->image) {
                Storage::disk('public')->delete($model->image);
            }
        });
    }

    /**
     * Absolute URL of the photo, or null when the record has no photo yet.
     *
     * @return Attribute<string|null, never>
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(
            fn (): ?string => $this->image ? '/storage/'.$this->image : null,
        );
    }
}
