<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProgramRequest;
use App\Http\Resources\ProgramDetailResource;
use App\Http\Resources\ProgramResource;
use App\Models\Program;
use App\Support\ImageOptimizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ProgramController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return ProgramResource::collection(Program::query()->ordered()->get());
    }

    /**
     * New programs are added after the last homepage card.
     */
    public function store(ProgramRequest $request): JsonResponse
    {
        $program = Program::query()->create([
            ...$this->attributes($request),
            'sort_order' => (int) Program::query()->max('sort_order') + 1,
        ]);

        return (new ProgramDetailResource($program))->response()->setStatusCode(201);
    }

    public function show(Program $program): ProgramDetailResource
    {
        return new ProgramDetailResource($program);
    }

    public function update(ProgramRequest $request, Program $program): ProgramDetailResource
    {
        $program->update($this->attributes($request));

        return new ProgramDetailResource($program);
    }

    public function destroy(Program $program): Response
    {
        $program->delete();

        return response()->noContent();
    }

    /**
     * Saves the homepage card order. `ids` must list every program exactly once.
     */
    public function reorder(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'size:'.Program::query()->count()],
            'ids.*' => ['integer', 'distinct', 'exists:programs,id'],
        ]);

        DB::transaction(function () use ($validated): void {
            foreach ($validated['ids'] as $position => $id) {
                Program::query()->whereKey($id)->update(['sort_order' => $position]);
            }
        });

        return $this->index();
    }

    /**
     * Validated fields plus the stored photo path. The previous photo file is
     * removed by the model when the path changes.
     *
     * @return array<string, mixed>
     */
    private function attributes(ProgramRequest $request): array
    {
        $attributes = $request->safe()->except(['image', 'remove_image']);

        if ($request->hasFile('image')) {
            $attributes['image'] = ImageOptimizer::store($request->file('image'), 'programs');
        } elseif ($request->boolean('remove_image')) {
            $attributes['image'] = null;
        }

        return $attributes;
    }
}
