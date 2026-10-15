<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\NewsCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\NewsRequest;
use App\Http\Resources\NewsDetailResource;
use App\Http\Resources\NewsResource;
use App\Models\News;
use App\Support\ImageOptimizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class NewsController extends Controller
{
    private const PER_PAGE = 15;

    /**
     * Every news article including drafts, newest first, with search and filters.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', Rule::enum(NewsCategory::class)],
            'status' => ['nullable', Rule::in(['published', 'scheduled', 'draft'])],
        ]);

        $news = News::query()
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $pattern = '%'.addcslashes($search, '%_\\').'%';
                $query->where(fn (Builder $query) => $query->where('title', 'like', $pattern)->orWhere('excerpt', 'like', $pattern));
            })
            ->when($filters['category'] ?? null, fn (Builder $query, string $category) => $query->where('category', $category))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => match ($status) {
                'published' => $query->published(),
                'scheduled' => $query->where('is_published', true)->where('published_at', '>', now()),
                'draft' => $query->where('is_published', false),
            })
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return NewsResource::collection($news);
    }

    public function store(NewsRequest $request): JsonResponse
    {
        $news = News::query()->create($this->attributes($request));

        return (new NewsDetailResource($news))->response()->setStatusCode(201);
    }

    public function show(News $news): NewsDetailResource
    {
        return new NewsDetailResource($news);
    }

    public function update(NewsRequest $request, News $news): NewsDetailResource
    {
        $news->update($this->attributes($request));

        return new NewsDetailResource($news);
    }

    public function destroy(News $news): Response
    {
        $news->delete();

        return response()->noContent();
    }

    /**
     * Validated fields plus the stored photo path. The previous photo file is
     * removed by the model when the path changes.
     *
     * @return array<string, mixed>
     */
    private function attributes(NewsRequest $request): array
    {
        $attributes = $request->safe()->except(['image', 'remove_image']);

        if ($request->hasFile('image')) {
            $attributes['image'] = ImageOptimizer::store($request->file('image'), 'news');
        } elseif ($request->boolean('remove_image')) {
            $attributes['image'] = null;
        }

        return $attributes;
    }
}
