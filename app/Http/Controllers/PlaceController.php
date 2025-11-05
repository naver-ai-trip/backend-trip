<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchNearbyPlacesRequest;
use App\Http\Requests\SearchPlacesRequest;
use App\Http\Requests\StorePlaceRequest;
use App\Http\Resources\PlaceResource;
use App\Models\Place;
use App\Services\Naver\LocalSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * PlaceController
 *
 * Handles place search, retrieval, and management using NAVER Local Search API.
 */
class PlaceController extends Controller
{
    public function __construct(
        private LocalSearchService $localSearchService
    ) {
    }

    /**
     * Search for places using text query.
     *
     * @OA\Post(
     *     path="/api/places/search",
     *     summary="Search for places by text query",
     *     tags={"Places"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"query"},
     *             @OA\Property(property="query", type="string", example="Tokyo Tower")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Search results from NAVER Places",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Place")),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="total", type="integer"),
     *                 @OA\Property(property="query", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError"),
     *     @OA\Response(response=503, description="NAVER API service unavailable")
     * )
     */
    public function search(SearchPlacesRequest $request): JsonResponse
    {
        $results = $this->localSearchService->search(
            query: $request->input('query'),
            display: 5
        );

        if ($results === null) {
            return response()->json([
                'message' => 'Place search service is currently unavailable',
                'data' => []
            ], 503);
        }

        return response()->json([
            'data' => $results,
            'meta' => [
                'total' => count($results),
                'query' => $request->input('query')
            ]
        ]);
    }

    /**
     * Search for nearby places by coordinates.
     *
     * @OA\Post(
     *     path="/api/places/search-nearby",
     *     summary="Search for places near specific coordinates",
     *     tags={"Places"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"latitude", "longitude"},
     *             @OA\Property(property="latitude", type="number", format="double", example=35.6762),
     *             @OA\Property(property="longitude", type="number", format="double", example=139.6503),
     *             @OA\Property(property="query", type="string", example="restaurant"),
     *             @OA\Property(property="radius", type="integer", example=1000, description="Search radius in meters (default: 500)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Nearby places from NAVER Local Search",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Place")),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="total", type="integer"),
     *                 @OA\Property(property="search_location", type="object",
     *                     @OA\Property(property="latitude", type="number"),
     *                     @OA\Property(property="longitude", type="number")
     *                 ),
     *                 @OA\Property(property="radius", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function searchNearby(SearchNearbyPlacesRequest $request): JsonResponse
    {
        $radius = $request->getRadius();

        $results = $this->localSearchService->searchNearby(
            latitude: $request->input('latitude'),
            longitude: $request->input('longitude'),
            radiusMeters: $radius,
            query: $request->input('query'),
            display: 10
        );

        return response()->json([
            'data' => $results,
            'meta' => [
                'total' => count($results),
                'search_location' => [
                    'latitude' => $request->input('latitude'),
                    'longitude' => $request->input('longitude'),
                ],
                'radius' => $radius
            ]
        ]);
    }

    /**
     * Get place details by NAVER place ID.
     *
     * @OA\Get(
     *     path="/api/places/naver/{naverPlaceId}",
     *     summary="Get place details by NAVER place ID",
     *     tags={"Places"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="naverPlaceId",
     *         in="path",
     *         description="NAVER place ID",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Place details from NAVER Maps",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/Place")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function getByNaverId(string $naverPlaceId): JsonResponse
    {
        $placeDetails = $this->localSearchService->getPlaceDetails($naverPlaceId);

        if ($placeDetails === null) {
            return response()->json([
                'message' => 'Place not found on NAVER Maps'
            ], 404);
        }

        return response()->json([
            'data' => $placeDetails
        ]);
    }

    /**
     * Store a place from NAVER to database.
     *
     * @OA\Post(
     *     path="/api/places",
     *     summary="Save a place from NAVER to database",
     *     tags={"Places"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"naver_place_id"},
     *             @OA\Property(property="naver_place_id", type="string", example="1234567890"),
     *             @OA\Property(property="fetch_details", type="boolean", example=true, description="Fetch full details from NAVER (default: false)"),
     *             @OA\Property(property="name", type="string", example="Tokyo Tower", description="Used only if fetch_details is false"),
     *             @OA\Property(property="latitude", type="number", format="double", example=35.6586, description="Used only if fetch_details is false"),
     *             @OA\Property(property="longitude", type="number", format="double", example=139.7454, description="Used only if fetch_details is false")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Place saved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Place saved successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Place")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Place already exists",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Place already exists"),
     *             @OA\Property(property="data", ref="#/components/schemas/Place")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function store(StorePlaceRequest $request): JsonResponse
    {
        $naverPlaceId = $request->input('naver_place_id');

        // Check if place already exists
        $existingPlace = Place::where('naver_place_id', $naverPlaceId)->first();

        if ($existingPlace) {
            return response()->json([
                'message' => 'Place already exists',
                'data' => new PlaceResource($existingPlace)
            ]);
        }

        // Fetch details from NAVER if requested
        if ($request->input('fetch_details', false)) {
            $placeDetails = $this->localSearchService->getPlaceDetails($naverPlaceId);

            if ($placeDetails === null) {
                return response()->json([
                    'message' => 'Unable to fetch place details from NAVER'
                ], 422);
            }

            $place = Place::create([
                'naver_place_id' => $naverPlaceId,
                'name' => $placeDetails['name'],
                'category' => $placeDetails['category'],
                'address' => $placeDetails['address'],
                'lat' => $placeDetails['latitude'],
                'lng' => $placeDetails['longitude'],
            ]);
        } else {
            // Create minimal place entry
            $place = Place::create([
                'naver_place_id' => $naverPlaceId,
                'name' => $request->input('name', 'Unknown Place'),
                'lat' => $request->input('latitude', 0),
                'lng' => $request->input('longitude', 0),
            ]);
        }

        return response()->json([
            'message' => 'Place saved successfully',
            'data' => new PlaceResource($place)
        ], 201);
    }

    /**
     * Display a listing of saved places.
     *
     * @OA\Get(
     *     path="/api/places",
     *     summary="List all saved places from database",
     *     tags={"Places"},
     *     security={{"sanctum":{}}},
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
     *         description="List of saved places with reviews",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Place")),
     *             @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized")
     * )
     */
    public function index(): AnonymousResourceCollection
    {
        $places = Place::with('reviews')->paginate(15);

        return PlaceResource::collection($places);
    }

    /**
     * Display the specified place from database.
     *
     * @OA\Get(
     *     path="/api/places/{place}",
     *     summary="View a saved place with reviews and favorites",
     *     tags={"Places"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="place",
     *         in="path",
     *         description="Place ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Place details with relationships",
     *         @OA\JsonContent(ref="#/components/schemas/Place")
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function show(Place $place): PlaceResource
    {
        $place->load('reviews', 'favorites');

        return new PlaceResource($place);
    }

    /**
     * Update the specified place.
     *
     * @OA\Patch(
     *     path="/api/places/{place}",
     *     summary="Update a saved place",
     *     tags={"Places"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="place",
     *         in="path",
     *         description="Place ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Tokyo Tower"),
     *             @OA\Property(property="category", type="string", example="Landmark"),
     *             @OA\Property(property="address", type="string", example="4 Chome-2-8 Shibakoen, Minato City, Tokyo"),
     *             @OA\Property(property="lat", type="number", format="double", example=35.6586),
     *             @OA\Property(property="lng", type="number", format="double", example=139.7454)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Place updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Place")
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function update(Request $request, Place $place): PlaceResource
    {
        $place->update($request->only([
            'name',
            'category',
            'address',
            'lat',
            'lng'
        ]));

        return new PlaceResource($place);
    }

    /**
     * Remove the specified place.
     *
     * @OA\Delete(
     *     path="/api/places/{place}",
     *     summary="Delete a saved place",
     *     tags={"Places"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="place",
     *         in="path",
     *         description="Place ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Place deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Place deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function destroy(Place $place): JsonResponse
    {
        $place->delete();

        return response()->json([
            'message' => 'Place deleted successfully'
        ]);
    }
}

