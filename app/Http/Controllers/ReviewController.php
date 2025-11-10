<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Http\Requests\UpdateReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\MapCheckpoint;
use App\Models\Place;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReviewController extends Controller
{

    /**
     * Display a listing of reviews.
     *
     * @OA\Get(
     *     path="/api/reviews",
     *     summary="List reviews with filters",
     *     tags={"Reviews"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="reviewable_type",
     *         in="query",
     *         description="Filter by type (place or checkpoint)",
     *         @OA\Schema(type="string", enum={"place", "checkpoint"})
     *     ),
     *     @OA\Parameter(
     *         name="reviewable_id",
     *         in="query",
     *         description="Filter by resource ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="rating",
     *         in="query",
     *         description="Filter by rating (1-5)",
     *         @OA\Schema(type="integer", minimum=1, maximum=5)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Review list",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Review")),
     *             @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized")
     * )
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Review::query();

        // Filter by reviewable_type
        if ($request->has('reviewable_type')) {
            $type = $request->input('reviewable_type');
            $modelClass = $type === 'place' ? Place::class : MapCheckpoint::class;
            $query->where('reviewable_type', $modelClass);
        }

        // Filter by reviewable_id
        if ($request->has('reviewable_id')) {
            $query->where('reviewable_id', $request->input('reviewable_id'));
        }

        // Filter by rating
        if ($request->has('rating')) {
            $query->rating($request->integer('rating'));
        }

        // Load relationships
        $query->with(['reviewable', 'user']);

        // Paginate results
        $perPage = $request->input('per_page', 15);
        $reviews = $query->paginate($perPage);

        return ReviewResource::collection($reviews);
    }

    /**
     * Store a newly created review with optional images.
     *
     * @OA\Post(
     *     path="/api/reviews",
     *     summary="Create a review for a place or checkpoint with optional images",
     *     description="Creates a review with automatic NAVER Green-Eye content moderation for uploaded images",
     *     tags={"Reviews"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"reviewable_type", "reviewable_id", "rating"},
     *                 @OA\Property(property="reviewable_type", type="string", enum={"place", "map_checkpoint"}, example="place", description="Type of entity being reviewed"),
     *                 @OA\Property(property="reviewable_id", type="integer", example=1, description="ID of the entity being reviewed"),
     *                 @OA\Property(property="rating", type="integer", minimum=1, maximum=5, example=5, description="Rating from 1 to 5 stars"),
     *                 @OA\Property(property="comment", type="string", nullable=true, example="Amazing view!", description="Optional review comment (max 1000 chars)"),
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
     *         description="Review created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/Review")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function store(StoreReviewRequest $request): ReviewResource
    {
        // Convert simple type to full class name
        $reviewableType = $request->input('reviewable_type');
        $modelClass = $reviewableType === 'place' ? Place::class : MapCheckpoint::class;

        $review = Review::create([
            'user_id' => $request->user()->id,
            'reviewable_type' => $modelClass,
            'reviewable_id' => $request->input('reviewable_id'),
            'rating' => $request->input('rating'),
            'comment' => $request->input('comment'),
        ]);

        // Handle image uploads if present
        if ($request->hasFile('images')) {
            $imagePaths = [];

            foreach ($request->file('images') as $image) {
                // Generate unique filename
                $uuid = Str::uuid();
                $extension = $image->getClientOriginalExtension();
                $path = "reviews/{$review->id}/{$uuid}.{$extension}";

                // Upload to public storage
                Storage::disk(config('filesystems.public_disk'))->put($path, file_get_contents($image->getRealPath()));
                $imagePaths[] = $path;

                // Dispatch job for asynchronous moderation
                \App\Jobs\ProcessImageModeration::dispatch('review', $review->id, $path);
            }

            // Update review with images (moderation will be done asynchronously)
            $review->update([
                'images' => $imagePaths,
            ]);
        }

        return new ReviewResource($review);
    }

    /**
     * Display the specified review.
     *
     * @OA\Get(
     *     path="/api/reviews/{review}",
     *     summary="Get review details",
     *     tags={"Reviews"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="review",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Review details",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/Review")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function show(Review $review): ReviewResource
    {
        // Load relationships
        $review->load(['reviewable', 'user']);
        
        return new ReviewResource($review);
    }

    /**
     * Update the specified review.
     *
     * @OA\Patch(
     *     path="/api/reviews/{review}",
     *     summary="Update a review",
     *     tags={"Reviews"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="review",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="rating", type="integer", minimum=1, maximum=5),
     *             @OA\Property(property="comment", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Review updated",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/Review")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function update(UpdateReviewRequest $request, Review $review): ReviewResource
    {
        $this->authorize('update', $review);

        $review->update($request->only([
            'rating',
            'comment',
        ]));

        return new ReviewResource($review);
    }

    /**
     * Remove the specified review.
     *
     * @OA\Delete(
     *     path="/api/reviews/{review}",
     *     summary="Delete a review",
     *     tags={"Reviews"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="review",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=204, description="Review deleted"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function destroy(Review $review): Response
    {
        $this->authorize('delete', $review);

        $review->delete();

        return response()->noContent();
    }
}
