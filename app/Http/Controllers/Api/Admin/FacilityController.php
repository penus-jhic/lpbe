<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\FacilityCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FacilityRequest;
use App\Http\Resources\FacilityResource;
use App\Models\Facility;
use App\Support\ImageOptimizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class FacilityController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return FacilityResource::collection(Facility::query()->ordered()->with('images')->get());
    }

    /**
     * New facilities are added at the end of the list.
     */
    public function store(FacilityRequest $request): JsonResponse
    {
        $facility = DB::transaction(function () use ($request): Facility {
            $facility = Facility::query()->create([
                ...$this->attributes($request),
                'sort_order' => (int) Facility::query()->max('sort_order') + 1,
            ]);

            $this->storeNewImages($request, $facility, startAt: 0);

            return $facility;
        });

        return (new FacilityResource($facility->load('images')))->response()->setStatusCode(201);
    }

    public function show(Facility $facility): FacilityResource
    {
        return new FacilityResource($facility->load('images'));
    }

    public function update(FacilityRequest $request, Facility $facility): FacilityResource
    {
        DB::transaction(function () use ($request, $facility): void {
            $facility->update($this->attributes($request));

            if ($request->has('image_ids')) {
                $this->syncKeptImages($facility, $request->validated('image_ids'));
            }

            // reorder(): the relation is ordered, and MySQL rejects ORDER BY next to MAX() without GROUP BY
            $nextPosition = (int) $facility->images()->reorder()->max('sort_order') + 1;
            $this->storeNewImages($request, $facility, startAt: $nextPosition);
        });

        return new FacilityResource($facility->load('images'));
    }

    public function destroy(Facility $facility): Response
    {
        $facility->delete();

        return response()->noContent();
    }

    /**
     * Saves the display order. `ids` must list every facility exactly once.
     */
    public function reorder(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'size:'.Facility::query()->count()],
            'ids.*' => ['integer', 'distinct', 'exists:facilities,id'],
        ]);

        DB::transaction(function () use ($validated): void {
            foreach ($validated['ids'] as $position => $id) {
                Facility::query()->whereKey($id)->update(['sort_order' => $position]);
            }
        });

        return $this->index();
    }

    /**
     * Major codes only apply to practice rooms, so they are cleared for support facilities.
     *
     * @return array<string, mixed>
     */
    private function attributes(FacilityRequest $request): array
    {
        $attributes = $request->safe()->except(['images', 'image_ids']);

        if ($attributes['category'] !== FacilityCategory::Praktik->value) {
            $attributes['majors'] = [];
        }

        return $attributes;
    }

    /**
     * Deletes photos missing from $keptIds (their files go with them) and orders the rest as listed.
     *
     * @param  list<int>  $keptIds
     */
    private function syncKeptImages(Facility $facility, array $keptIds): void
    {
        $facility->images()->whereKeyNot($keptIds)->get()->each->delete();

        foreach ($keptIds as $position => $id) {
            $facility->images()->whereKey($id)->update(['sort_order' => $position]);
        }
    }

    private function storeNewImages(FacilityRequest $request, Facility $facility, int $startAt): void
    {
        foreach ($request->file('images', []) as $offset => $file) {
            $facility->images()->create([
                'path' => ImageOptimizer::store($file, 'facilities'),
                'sort_order' => $startAt + $offset,
            ]);
        }
    }
}
