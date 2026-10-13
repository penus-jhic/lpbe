<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

/**
 * A full activity for its detail page, including its body blocks (empty when the admin wrote none).
 */
class ActivityDetailResource extends ActivityResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'body' => $this->body ?? [],
        ];
    }
}
