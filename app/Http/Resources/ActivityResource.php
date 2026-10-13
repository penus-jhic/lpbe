<?php

namespace App\Http\Resources;

use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Activity card data. Keys match the `Activity` type in the frontend (src/data/activities.ts).
 *
 * @mixin Activity
 */
class ActivityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'category' => $this->category->value,
            'title' => $this->title,
            'description' => $this->description,
            'icon' => $this->icon,
            'image' => $this->image_url,
            'schedule' => $this->schedule,
            'sort_order' => $this->sort_order,
        ];
    }
}
