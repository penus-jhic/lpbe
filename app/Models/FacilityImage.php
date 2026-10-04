<?php

namespace App\Models;

use Database\Factories\FacilityImageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $facility_id
 * @property string $path
 * @property int $sort_order
 * @property-read string $url
 */
#[Fillable(['path', 'sort_order'])]
class FacilityImage extends Model
{
    /** @use HasFactory<FacilityImageFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::deleted(function (FacilityImage $image): void {
            Storage::disk('public')->delete($image->path);
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Facility, $this>
     */
    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    /**
     * @return Attribute<string, never>
     */
    protected function url(): Attribute
    {
        return Attribute::get(fn (): string => '/storage/'.$this->path);
    }
}
