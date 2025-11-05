<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CommentController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *     path="/api/comments",
     *     summary="List comments with filtering by entity type and ID",
     *     tags={"Comments"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="entity_type",
     *         in="query",
     *         description="Filter by entity type",
     *         required=false,
     *         @OA\Schema(type="string", enum={"trip", "map_checkpoint", "trip_diary"})
     *     ),
     *     @OA\Parameter(
     *         name="entity_id",
     *         in="query",
     *         description="Filter by entity ID",
     *         required=false,
     *         @OA\Schema(type="integer")
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
     *         description="List of comments with user and entity relationships",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Comment")),
     *             @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized")
     * )
     */
    public function index(Request $request)
    {
        $query = Comment::with(['user', 'entity']);

        // Filter by entity_type
        if ($request->has('entity_type')) {
            $entityClass = match ($request->input('entity_type')) {
                'trip' => \App\Models\Trip::class,
                'map_checkpoint' => \App\Models\MapCheckpoint::class,
                'trip_diary' => \App\Models\TripDiary::class,
                default => null,
            };

            if ($entityClass) {
                $query->where('entity_type', $entityClass);
            }
        }

        // Filter by entity_id
        if ($request->has('entity_id')) {
            $query->where('entity_id', $request->input('entity_id'));
        }

        $comments = $query->paginate(15);

        return CommentResource::collection($comments);
    }

    /**
     * Store a newly created comment with optional images.
     *
     * @OA\Post(
     *     path="/api/comments",
     *     summary="Create a comment on a trip, checkpoint, or diary with optional images",
     *     description="Creates a comment with automatic NAVER Green-Eye content moderation for uploaded images",
     *     tags={"Comments"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"entity_type", "entity_id", "content"},
     *                 @OA\Property(property="entity_type", type="string", enum={"trip", "map_checkpoint", "trip_diary"}, example="trip", description="Type of entity being commented on"),
     *                 @OA\Property(property="entity_id", type="integer", example=5, description="ID of the entity being commented on"),
     *                 @OA\Property(property="content", type="string", example="Great trip! Loved visiting this place.", description="Comment content (max 2000 chars)"),
     *                 @OA\Property(
     *                     property="images",
     *                     type="array",
     *                     nullable=true,
     *                     description="Optional images (max 5, 10MB each, jpeg/jpg/png/gif/webp)",
     *                     @OA\Items(type="string", format="binary")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Comment created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Comment")
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function store(StoreCommentRequest $request)
    {
        $comment = Comment::create([
            'user_id' => auth()->id(),
            'entity_type' => $request->input('entity_class'),
            'entity_id' => $request->input('entity_id'),
            'content' => $request->input('content'),
        ]);

        // Handle image uploads if present
        if ($request->hasFile('images')) {
            $imagePaths = [];

            foreach ($request->file('images') as $image) {
                // Generate unique filename
                $uuid = Str::uuid();
                $extension = $image->getClientOriginalExtension();
                $path = "comments/{$comment->id}/{$uuid}.{$extension}";

                // Upload to storage
                Storage::disk('public')->put($path, file_get_contents($image->getRealPath()));
                $imagePaths[] = $path;

                // Dispatch job for asynchronous moderation
                \App\Jobs\ProcessImageModeration::dispatch('comment', $comment->id, $path);
            }

            // Update comment with images (moderation will be done asynchronously)
            $comment->update([
                'images' => $imagePaths,
            ]);
        }

        $comment->load(['user', 'entity']);

        return new CommentResource($comment);
    }

    /**
     * Display the specified resource.
     *
     * @OA\Get(
     *     path="/api/comments/{comment}",
     *     summary="View a single comment",
     *     tags={"Comments"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="comment",
     *         in="path",
     *         description="Comment ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Comment details with user and entity relationships",
     *         @OA\JsonContent(ref="#/components/schemas/Comment")
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function show(Comment $comment)
    {
        $comment->load(['user', 'entity']);

        return new CommentResource($comment);
    }

    /**
     * Update the specified resource in storage.
     *
     * @OA\Patch(
     *     path="/api/comments/{comment}",
     *     summary="Update comment content (owner only)",
     *     tags={"Comments"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="comment",
     *         in="path",
     *         description="Comment ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"content"},
     *             @OA\Property(property="content", type="string", example="Updated comment text")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Comment updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Comment")
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function update(UpdateCommentRequest $request, Comment $comment)
    {
        $this->authorize('update', $comment);

        $comment->update([
            'content' => $request->input('content'),
        ]);

        $comment->load(['user', 'entity']);

        return new CommentResource($comment);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @OA\Delete(
     *     path="/api/comments/{comment}",
     *     summary="Delete a comment (owner only)",
     *     tags={"Comments"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="comment",
     *         in="path",
     *         description="Comment ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Comment deleted successfully"
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function destroy(Comment $comment)
    {
        $this->authorize('delete', $comment);

        $comment->delete();

        return response()->noContent();
    }
}
