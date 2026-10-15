<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\ActivityCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ActivityRequest;
use App\Http\Resources\ActivityDetailResource;
use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use App\Support\ImageOptimizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ActivityController extends Controller
{
    /**
     * Every activity, or only one category when `?category=` is given (each admin page lists one category).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'category' => ['sometimes', Rule::enum(ActivityCategory::class)],
        ]);

        return ActivityResource::collection(
            Activity::query()
                ->when($validated['category'] ?? null, fn ($query, string $category) => $query->where('category', $category))
                ->ordered()
                ->get(),
        );
    }

    /**
     * New activities are added at the end of their category.
     */
    public function store(ActivityRequest $request): JsonResponse
    {
        $category = $request->enum('category', ActivityCategory::class);

        $activity = Activity::query()->create([
            ...$this->attributes($request),
            'sort_order' => Activity::nextSortOrder($category),
        ]);

        return (new ActivityDetailResource($activity))->response()->setStatusCode(201);
    }

    public function show(Activity $activity): ActivityDetailResource
    {
        return new ActivityDetailResource($activity);
    }

    /**
     * An activity moved to the other category goes to the end of that category.
     */
    public function update(ActivityRequest $request, Activity $activity): ActivityDetailResource
    {
        $attributes = $this->attributes($request);
        $category = $request->enum('category', ActivityCategory::class);

        if ($category !== $activity->category) {
            $attributes['sort_order'] = Activity::nextSortOrder($category);
        }

        $activity->update($attributes);

        return new ActivityDetailResource($activity);
    }

    public function destroy(Activity $activity): Response
    {
        $activity->delete();

        return response()->noContent();
    }

    /**
     * Saves the order of one category. `ids` must list every activity of that category exactly once.
     */
    public function reorder(Request $request): AnonymousResourceCollection
    {
        $category = $request->validate([
            'category' => ['required', Rule::enum(ActivityCategory::class)],
        ])['category'];

        $validated = $request->validate([
            'ids' => ['required', 'array', 'size:'.Activity::query()->where('category', $category)->count()],
            'ids.*' => ['integer', 'distinct', Rule::exists('activities', 'id')->where('category', $category)],
        ]);

        DB::transaction(function () use ($validated): void {
            foreach ($validated['ids'] as $position => $id) {
                Activity::query()->whereKey($id)->update(['sort_order' => $position]);
            }
        });

        return $this->index($request);
    }

    /**
     * Validated fields plus the stored photo path. The previous photo file is
     * removed by the model when the path changes.
     *
     * @return array<string, mixed>
     */
    private function attributes(ActivityRequest $request): array
    {
        $attributes = $request->safe()->except(['image', 'remove_image']);

        if ($request->hasFile('image')) {
            $attributes['image'] = ImageOptimizer::store($request->file('image'), 'activities');
        } elseif ($request->boolean('remove_image')) {
            $attributes['image'] = null;
        }

        return $attributes;
    }
}
