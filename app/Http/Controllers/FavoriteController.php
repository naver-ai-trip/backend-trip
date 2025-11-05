<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFavoriteRequest;
use App\Http\Resources\FavoriteResource;
use App\Models\Favorite;
use App\Models\MapCheckpoint;
use App\Models\Place;
use App\Models\Trip;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    /**
     * Display a listing of the user's favorites.
     *
     * @OA\Get(
     *     path="/api/favorites",
     *     summary="List the authenticated user's favorites",
     *     tags={"Favorites"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="favoritable_type",
     *         in="query",
     *         description="Filter by favoritable type",
     *         required=false,
     *         @OA\Schema(type="string", enum={"place", "trip", "map_checkpoint"})
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
     *         description="List of user's favorites with favoritable relationships",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Favorite")),
     *             @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized")
     * )
     */
    public function index(Request $request)
    {
        $query = Favorite::where('user_id', auth()->id())
            ->with('favoritable')
            ->latest();

        // Filter by favoritable_type
        if ($request->has('favoritable_type')) {
            $type = $request->input('favoritable_type');
            $modelClass = match ($type) {
                'place' => Place::class,
                'trip' => Trip::class,
                'map_checkpoint' => MapCheckpoint::class,
                default => null,
            };

            if ($modelClass) {
                $query->where('favoritable_type', $modelClass);
            }
        }

        $favorites = $query->paginate(15);

        return FavoriteResource::collection($favorites);
    }

    /**
     * Store a newly created favorite.
     *
     * @OA\Post(
     *     path="/api/favorites",
     *     summary="Add a favorite (place, trip, or checkpoint)",
     *     tags={"Favorites"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"favoritable_type", "favoritable_id"},
     *             @OA\Property(property="favoritable_type", type="string", enum={"place", "trip", "map_checkpoint"}, example="place"),
     *             @OA\Property(property="favoritable_id", type="integer", example=5)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Favorite added successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Favorite")
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error or duplicate favorite",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
     *     )
     * )
     */
    public function store(StoreFavoriteRequest $request)
    {
        $validated = $request->validated();

        // Convert simple type to model class
        $favoritableType = match ($validated['favoritable_type']) {
            'place' => Place::class,
            'trip' => Trip::class,
            'map_checkpoint' => MapCheckpoint::class,
        };

        $favorite = Favorite::create([
            'user_id' => auth()->id(),
            'favoritable_type' => $favoritableType,
            'favoritable_id' => $validated['favoritable_id'],
        ]);

        return new FavoriteResource($favorite);
    }

    /**
     * Display the specified favorite.
     *
     * @OA\Get(
     *     path="/api/favorites/{favorite}",
     *     summary="View a single favorite",
     *     tags={"Favorites"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="favorite",
     *         in="path",
     *         description="Favorite ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Favorite details with favoritable relationship",
     *         @OA\JsonContent(ref="#/components/schemas/Favorite")
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function show(Favorite $favorite)
    {
        return new FavoriteResource($favorite->load('favoritable'));
    }

    /**
     * Remove the specified favorite.
     *
     * @OA\Delete(
     *     path="/api/favorites/{favorite}",
     *     summary="Remove a favorite (unfavorite)",
     *     tags={"Favorites"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="favorite",
     *         in="path",
     *         description="Favorite ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Favorite deleted successfully"
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function destroy(Favorite $favorite)
    {
        $this->authorize('delete', $favorite);

        $favorite->delete();

        return response()->noContent();
    }
}
