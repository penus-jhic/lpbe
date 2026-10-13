<?php

namespace App\Models;

use App\Enums\ActivityCategory;
use App\Models\Concerns\HasPublicImage;
use App\Models\Concerns\HasUniqueSlug;
use Database\Factories\ActivityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A student activity: a Talent Day class or an extracurricular club ("ekskul"), each with its own detail page.
 *
 * @property int $id
 * @property string $slug
 * @property ActivityCategory $category
 * @property string $title
 * @property string $description
 * @property string $icon
 * @property string|null $image
 * @property string|null $schedule
 * @property list<string|array<string, mixed>>|null $body
 * @property int $sort_order
 * @property-read string|null $image_url
 */
#[Fillable(['category', 'slug', 'title', 'description', 'icon', 'image', 'schedule', 'body', 'sort_order'])]
class Activity extends Model
{
    /** @use HasFactory<ActivityFactory> */
    use HasFactory, HasPublicImage, HasUniqueSlug;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => ActivityCategory::class,
            'body' => 'array',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Order used by the homepage sections and the admin lists. Each category is ordered on its own.
     */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Position right after the last activity of the category, used for new or moved activities.
     */
    public static function nextSortOrder(ActivityCategory $category): int
    {
        $last = static::query()->where('category', $category)->max('sort_order');

        return $last === null ? 0 : (int) $last + 1;
    }
}
