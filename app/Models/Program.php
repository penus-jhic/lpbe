<?php

namespace App\Models;

use App\Models\Concerns\HasPublicImage;
use App\Models\Concerns\HasUniqueSlug;
use Database\Factories\ProgramFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A featured school program ("program unggulan").
 *
 * @property int $id
 * @property string $slug
 * @property string $title
 * @property string $description
 * @property string $icon
 * @property string|null $image
 * @property string $audience
 * @property string $schedule
 * @property list<string|array<string, mixed>> $body
 * @property int $sort_order
 * @property-read string|null $image_url
 */
#[Fillable(['slug', 'title', 'description', 'icon', 'image', 'audience', 'schedule', 'body', 'sort_order'])]
class Program extends Model
{
    /** @use HasFactory<ProgramFactory> */
    use HasFactory, HasPublicImage, HasUniqueSlug;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'body' => 'array',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Order used by the homepage cards and the admin list.
     */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('id');
    }
}
