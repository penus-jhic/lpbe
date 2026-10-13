<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityDetailResource;
use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ActivityController extends Controller
{
    private const OTHERS_COUNT = 3;

    /**
     * All Talent Day classes and extracurricular clubs in the order set by the admin.
     * The frontend splits them by category (homepage slides and the /kegiatan page).
     */
    public function index(): AnonymousResourceCollection
    {
        return ActivityResource::collection(Activity::query()->ordered()->get());
    }

    /**
     * One activity, plus the activities of the same category that come after it
     * (wrapping around to the first) for the "Kegiatan Lainnya" section.
     */
    public function show(Request $request, string $slug): ActivityDetailResource
    {
        $activity = Activity::query()->where('slug', $slug)->firstOrFail();
        $siblings = Activity::query()->where('category', $activity->category)->ordered()->get();
        $index = $siblings->search(fn (Activity $sibling): bool => $sibling->is($activity));

        $others = $siblings->slice($index + 1)
            ->concat($siblings->take($index))
            ->take(self::OTHERS_COUNT)
            ->values();

        return (new ActivityDetailResource($activity))->additional([
            'others' => ActivityResource::collection($others)->resolve($request),
        ]);
    }
}
