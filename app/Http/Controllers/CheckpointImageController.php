<?php

namespace App\Http\Controllers;

use App\Models\MapCheckpoint;
use App\Models\CheckpointImage;
use App\Http\Requests\StoreCheckpointImageRequest;
use App\Http\Requests\UpdateCheckpointImageRequest;
use App\Http\Resources\CheckpointImageResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CheckpointImageController extends Controller
{

    /**
     * Display a listing of the checkpoint images.
     *
     * @OA\Get(
     *     path="/api/checkpoints/{checkpoint}/images",
     *     summary="List images for a checkpoint",
     *     tags={"Checkpoint Images"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="checkpoint",
     *         in="path",
     *         description="Checkpoint ID",
     *         required=true,
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
     *         description="List of checkpoint images ordered by uploaded_at desc",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/CheckpointImage")),
     *             @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function index(MapCheckpoint $checkpoint): AnonymousResourceCollection
    {
        $images = CheckpointImage::query()
            ->forCheckpoint($checkpoint->id)
            ->recent()
            ->paginate(15);

        return CheckpointImageResource::collection($images);
    }

    /**
     * Store a newly uploaded checkpoint image with automatic content moderation.
     *
     * @OA\Post(
     *     path="/api/checkpoints/{checkpoint}/images",
     *     summary="Upload an image to a checkpoint with NAVER Green-Eye moderation (trip owner only)",
     *     description="Uploads image with automatic content moderation for adult content and violence",
     *     tags={"Checkpoint Images"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="checkpoint",
     *         in="path",
     *         description="Checkpoint ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"image"},
     *                 @OA\Property(property="image", type="string", format="binary", description="Image file (jpeg, jpg, png, gif, webp, max 10MB)"),
     *                 @OA\Property(property="caption", type="string", example="Beautiful view from Tokyo Tower", description="Optional caption (max 500 chars)")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Image uploaded successfully",
     *         @OA\JsonContent(ref="#/components/schemas/CheckpointImage")
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function store(StoreCheckpointImageRequest $request, MapCheckpoint $checkpoint): JsonResponse
    {
        // Authorization: Check if user can upload images to this checkpoint
        $this->authorize('create', [CheckpointImage::class, $checkpoint]);

        $validated = $request->validated();

        // Handle file upload
        $file = $request->file('image');
        $extension = $file->getClientOriginalExtension();
        
        // Generate organized path: checkpoints/{trip_id}/{checkpoint_id}/{uuid}.{extension}
        $checkpoint->loadMissing('trip');
        $tripId = $checkpoint->trip_id;
        $checkpointId = $checkpoint->id;
        $uuid = Str::uuid();
        $path = "checkpoints/{$tripId}/{$checkpointId}/{$uuid}.{$extension}";
        
        // Store file on public disk
        Storage::disk(config('filesystems.public_disk'))->put($path, file_get_contents($file));

        // Create database record (moderation will be done asynchronously)
        $image = CheckpointImage::create([
            'map_checkpoint_id' => $checkpoint->id,
            'user_id' => $request->user()->id,
            'file_path' => $path,
            'caption' => $validated['caption'] ?? null,
            'uploaded_at' => now(),
        ]);

        // Dispatch job for asynchronous moderation
        \App\Jobs\ProcessImageModeration::dispatch('checkpoint_image', $image->id, $path);

        return (new CheckpointImageResource($image))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified checkpoint image.
     *
     * @OA\Get(
     *     path="/api/checkpoints/{checkpoint}/images/{image}",
     *     summary="View a checkpoint image",
     *     tags={"Checkpoint Images"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="checkpoint",
     *         in="path",
     *         description="Checkpoint ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="image",
     *         in="path",
     *         description="Image ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Image details with checkpoint and user relationships",
     *         @OA\JsonContent(ref="#/components/schemas/CheckpointImage")
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function show(MapCheckpoint $checkpoint, CheckpointImage $image): CheckpointImageResource
    {
        // Verify image belongs to this checkpoint
        if ($image->map_checkpoint_id !== $checkpoint->id) {
            abort(404);
        }

        $this->authorize('view', $image);

        // Eager load relationships for detailed view
        $image->load(['checkpoint', 'user']);

        return new CheckpointImageResource($image);
    }

    /**
     * Update the checkpoint image caption.
     *
     * @OA\Patch(
     *     path="/api/checkpoints/{checkpoint}/images/{image}",
     *     summary="Update image caption (uploader only)",
     *     tags={"Checkpoint Images"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="checkpoint",
     *         in="path",
     *         description="Checkpoint ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="image",
     *         in="path",
     *         description="Image ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"caption"},
     *             @OA\Property(property="caption", type="string", example="Updated caption", description="Max 500 characters")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Caption updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/CheckpointImage")
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function update(UpdateCheckpointImageRequest $request, MapCheckpoint $checkpoint, CheckpointImage $image): CheckpointImageResource
    {
        // Verify image belongs to this checkpoint
        if ($image->map_checkpoint_id !== $checkpoint->id) {
            abort(404);
        }

        $this->authorize('update', $image);

        $validated = $request->validated();
        $image->update($validated);

        return new CheckpointImageResource($image);
    }

    /**
     * Remove the checkpoint image and delete the file.
     *
     * @OA\Delete(
     *     path="/api/checkpoints/{checkpoint}/images/{image}",
     *     summary="Delete checkpoint image (uploader or trip owner)",
     *     tags={"Checkpoint Images"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="checkpoint",
     *         in="path",
     *         description="Checkpoint ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="image",
     *         in="path",
     *         description="Image ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Image deleted successfully (file and database record)"
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function destroy(MapCheckpoint $checkpoint, CheckpointImage $image): JsonResponse
    {
        // Verify image belongs to this checkpoint
        if ($image->map_checkpoint_id !== $checkpoint->id) {
            abort(404);
        }

        $this->authorize('delete', $image);

        // Delete file from storage
        Storage::disk(config('filesystems.public_disk'))->delete($image->file_path);

        // Delete database record
        $image->delete();

        return response()->json(null, 204);
    }
}
