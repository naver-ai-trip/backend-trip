<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttachTagRequest;
use App\Http\Requests\DetachTagRequest;
use App\Http\Resources\TagResource;
use App\Models\MapCheckpoint;
use App\Models\Place;
use App\Models\Tag;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TagController extends Controller
{
    /**
     * List all tags with optional search and sort.
     *
     * @OA\Get(
     *     path="/api/tags",
     *     summary="List all tags",
     *     tags={"Tags"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search tags by name",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         description="Sort by name",
     *         @OA\Schema(type="string", enum={"name"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tag list",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="slug", type="string"),
     *                 @OA\Property(property="usage_count", type="integer")
     *             )),
     *             @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized")
     * )
     */
    public function index(Request $request)
    {
        $query = Tag::query();

        // Search by name
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Sort alphabetically by name
        if ($request->query('sort') === 'name') {
            $query->orderBy('name');
        }

        $tags = $query->paginate(15);

        return TagResource::collection($tags);
    }

    /**
     * List popular tags by usage count.
     *
     * @OA\Get(
     *     path="/api/tags/popular",
     *     summary="List popular tags by usage count",
     *     tags={"Tags"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="min_count",
     *         in="query",
     *         description="Minimum usage count to filter tags (default: 10)",
     *         required=false,
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Results per page (default: 15)",
     *         required=false,
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of popular tags with pagination",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Tag")),
     *             @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized")
     * )
     */
    public function popular(Request $request)
    {
        $minCount = $request->integer('min_count', 10);

        $tags = Tag::popular($minCount)->paginate(15);

        return TagResource::collection($tags);
    }

    /**
     * Display a single tag.
     *
     * @OA\Get(
     *     path="/api/tags/{tag}",
     *     summary="View a single tag",
     *     tags={"Tags"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="tag",
     *         in="path",
     *         description="Tag ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tag details",
     *         @OA\JsonContent(ref="#/components/schemas/Tag")
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function show(Tag $tag)
    {
        return new TagResource($tag);
    }

    /**
     * Attach a tag to an entity (trip, place, or checkpoint).
     *
     * @OA\Post(
     *     path="/api/tags/attach",
     *     summary="Attach a tag to an entity (trip, place, or checkpoint)",
     *     tags={"Tags"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"tag_id", "taggable_type", "taggable_id"},
     *             @OA\Property(property="tag_id", type="integer", example=1),
     *             @OA\Property(property="taggable_type", type="string", enum={"trip", "place", "checkpoint"}, example="trip"),
     *             @OA\Property(property="taggable_id", type="integer", example=5)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Tag attached successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Tag attached successfully")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error or tag already attached",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Tag is already attached to this entity")
     *         )
     *     ),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function attach(AttachTagRequest $request)
    {
        $tag = Tag::findOrFail($request->tag_id);

        // Determine the taggable entity
        $taggable = $this->resolveTaggable($request->taggable_type, $request->taggable_id);

        // Check if tag is already attached
        if ($taggable->tags()->where('tag_id', $tag->id)->exists()) {
            return response()->json([
                'message' => 'Tag is already attached to this entity'
            ], 422);
        }

        // Attach tag to entity
        $taggable->tags()->attach($tag->id);

        // Increment usage count
        $tag->incrementUsage();

        return response()->json([
            'message' => 'Tag attached successfully'
        ], 201);
    }

    /**
     * Detach a tag from an entity.
     *
     * @OA\Delete(
     *     path="/api/tags/detach",
     *     summary="Detach a tag from an entity",
     *     tags={"Tags"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"tag_id", "taggable_type", "taggable_id"},
     *             @OA\Property(property="tag_id", type="integer", example=1),
     *             @OA\Property(property="taggable_type", type="string", enum={"trip", "place", "checkpoint"}, example="trip"),
     *             @OA\Property(property="taggable_id", type="integer", example=5)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tag detached successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Tag detached successfully")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
     *     ),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function detach(DetachTagRequest $request)
    {
        $tag = Tag::findOrFail($request->tag_id);

        // Determine the taggable entity
        $taggable = $this->resolveTaggable($request->taggable_type, $request->taggable_id);

        // Detach tag from entity
        $taggable->tags()->detach($tag->id);

        // Decrement usage count (won't go below 0)
        $tag->decrementUsage();

        return response()->json([
            'message' => 'Tag detached successfully'
        ]);
    }

    /**
     * Resolve the taggable entity based on type and id.
     */
    private function resolveTaggable(string $type, int $id)
    {
        return match ($type) {
            'trip' => Trip::findOrFail($id),
            'place' => Place::findOrFail($id),
            'checkpoint' => MapCheckpoint::findOrFail($id),
            default => abort(422, 'Invalid taggable type'),
        };
    }
}
