<?php

namespace App\Http\Controllers;

use App\Services\Naver\NaverMapsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * MapController
 *
 * Handles NAVER Maps API operations including geocoding, reverse geocoding, and directions.
 */
class MapController extends Controller
{
    public function __construct(
        private NaverMapsService $mapsService
    ) {
    }

    /**
     * Geocode an address to coordinates.
     *
     * @OA\Post(
     *     path="/api/maps/geocode",
     *     summary="Convert address to coordinates (Geocoding)",
     *     description="Uses NAVER Maps Geocoding API to convert an address or place name to latitude/longitude coordinates",
     *     tags={"Maps"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"query"},
     *             @OA\Property(property="query", type="string", example="강남역", description="Address or place name in Korean or English")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Geocoding successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="address", type="string", example="서울특별시 강남구 역삼동 819-7"),
     *                 @OA\Property(property="roadAddress", type="string", example="서울특별시 강남구 강남대로 396"),
     *                 @OA\Property(property="latitude", type="number", format="double", example=37.498095),
     *                 @OA\Property(property="longitude", type="number", format="double", example=127.027610)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Address not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Address not found")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function geocode(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->mapsService->geocode($request->input('query'));

        if ($result === null) {
            return response()->json([
                'success' => false,
                'message' => 'Address not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }

    /**
     * Reverse geocode coordinates to an address.
     *
     * @OA\Post(
     *     path="/api/maps/reverse-geocode",
     *     summary="Convert coordinates to address (Reverse Geocoding)",
     *     description="Uses NAVER Maps Reverse Geocoding API to convert latitude/longitude to a human-readable address",
     *     tags={"Maps"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"latitude", "longitude"},
     *             @OA\Property(property="latitude", type="number", format="double", example=37.5665, description="Latitude coordinate"),
     *             @OA\Property(property="longitude", type="number", format="double", example=126.9780, description="Longitude coordinate")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reverse geocoding successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="address", type="string", example="서울특별시 중구 태평로1가 31"),
     *                 @OA\Property(property="roadAddress", type="string", example="서울특별시 중구 세종대로 110"),
     *                 @OA\Property(property="area1", type="string", example="서울특별시", description="City/Province"),
     *                 @OA\Property(property="area2", type="string", example="중구", description="District"),
     *                 @OA\Property(property="area3", type="string", example="태평로1가", description="Neighborhood")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Address not found for coordinates",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Address not found")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function reverseGeocode(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->mapsService->reverseGeocode(
            $request->input('latitude'),
            $request->input('longitude')
        );

        if ($result === null) {
            return response()->json([
                'success' => false,
                'message' => 'Address not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }

    /**
     * Get driving directions between two points (Directions 5).
     *
     * @OA\Post(
     *     path="/api/maps/directions",
     *     summary="Get driving directions between two points",
     *     description="Uses NAVER Maps Directions 5 API to calculate optimal driving route. Supports 5 different route options.",
     *     tags={"Maps"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"start_lat", "start_lng", "goal_lat", "goal_lng"},
     *             @OA\Property(property="start_lat", type="number", format="double", example=37.5665, description="Start latitude"),
     *             @OA\Property(property="start_lng", type="number", format="double", example=126.9780, description="Start longitude"),
     *             @OA\Property(property="goal_lat", type="number", format="double", example=37.5172, description="Goal latitude"),
     *             @OA\Property(property="goal_lng", type="number", format="double", example=127.0473, description="Goal longitude"),
     *             @OA\Property(
     *                 property="option",
     *                 type="string",
     *                 enum={"trafast", "traoptimal", "tracomfort", "traavoidtoll", "traavoidcaronly"},
     *                 example="traoptimal",
     *                 description="Route option: trafast (traffic-aware fast), traoptimal (optimal), tracomfort (comfort), traavoidtoll (avoid tolls), traavoidcaronly (avoid car-only roads)"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Directions calculated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="distance", type="integer", example=5420, description="Total distance in meters"),
     *                 @OA\Property(property="duration", type="integer", example=900000, description="Estimated duration in milliseconds"),
     *                 @OA\Property(property="path", type="array", description="Array of [longitude, latitude] coordinates for the route",
     *                     @OA\Items(type="array", @OA\Items(type="number"))
     *                 ),
     *                 @OA\Property(property="tollFare", type="integer", example=1000, description="Toll fee in KRW (if applicable)"),
     *                 @OA\Property(property="taxiFare", type="integer", example=15000, description="Estimated taxi fare in KRW"),
     *                 @OA\Property(property="fuelPrice", type="integer", example=800, description="Estimated fuel cost in KRW")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Route not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Route not found")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function directions(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_lat' => 'required|numeric|between:-90,90',
            'start_lng' => 'required|numeric|between:-180,180',
            'goal_lat' => 'required|numeric|between:-90,90',
            'goal_lng' => 'required|numeric|between:-180,180',
            'option' => 'nullable|string|in:trafast,traoptimal,tracomfort,traavoidtoll,traavoidcaronly',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $options = [];
        if ($request->has('option')) {
            $options['option'] = $request->input('option');
        }

        $result = $this->mapsService->getDirections5(
            $request->input('start_lat'),
            $request->input('start_lng'),
            $request->input('goal_lat'),
            $request->input('goal_lng'),
            $options
        );

        if ($result === null) {
            return response()->json([
                'success' => false,
                'message' => 'Route not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }

    /**
     * Get driving directions with multiple waypoints (Directions 15).
     *
     * @OA\Post(
     *     path="/api/maps/directions-waypoints",
     *     summary="Get driving directions with up to 15 waypoints",
     *     description="Uses NAVER Maps Directions 15 API to calculate optimal driving route through multiple waypoints. Supports up to 15 intermediate stops.",
     *     tags={"Maps"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"start_lat", "start_lng", "goal_lat", "goal_lng"},
     *             @OA\Property(property="start_lat", type="number", format="double", example=37.5665, description="Start latitude"),
     *             @OA\Property(property="start_lng", type="number", format="double", example=126.9780, description="Start longitude"),
     *             @OA\Property(property="goal_lat", type="number", format="double", example=37.5172, description="Goal latitude"),
     *             @OA\Property(property="goal_lng", type="number", format="double", example=127.0473, description="Goal longitude"),
     *             @OA\Property(
     *                 property="waypoints",
     *                 type="array",
     *                 description="Array of waypoint objects with lat and lng properties (max 15)",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="lat", type="number", format="double", example=37.5400),
     *                     @OA\Property(property="lng", type="number", format="double", example=127.0000)
     *                 ),
     *                 example={{"lat": 37.5400, "lng": 127.0000}, {"lat": 37.5300, "lng": 127.0200}}
     *             ),
     *             @OA\Property(
     *                 property="option",
     *                 type="string",
     *                 enum={"trafast", "traoptimal", "tracomfort", "traavoidtoll", "traavoidcaronly"},
     *                 example="traoptimal",
     *                 description="Route option"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Directions with waypoints calculated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="distance", type="integer", example=12500, description="Total distance in meters"),
     *                 @OA\Property(property="duration", type="integer", example=1800000, description="Estimated duration in milliseconds"),
     *                 @OA\Property(property="path", type="array", description="Array of [longitude, latitude] coordinates for the entire route",
     *                     @OA\Items(type="array", @OA\Items(type="number"))
     *                 ),
     *                 @OA\Property(property="tollFare", type="integer", example=2000, description="Toll fee in KRW (if applicable)"),
     *                 @OA\Property(property="taxiFare", type="integer", example=30000, description="Estimated taxi fare in KRW"),
     *                 @OA\Property(property="fuelPrice", type="integer", example=1500, description="Estimated fuel cost in KRW"),
     *                 @OA\Property(property="waypoints_count", type="integer", example=2, description="Number of waypoints in route")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Route not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Route not found")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function directionsWithWaypoints(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_lat' => 'required|numeric|between:-90,90',
            'start_lng' => 'required|numeric|between:-180,180',
            'goal_lat' => 'required|numeric|between:-90,90',
            'goal_lng' => 'required|numeric|between:-180,180',
            'waypoints' => 'nullable|array|max:15',
            'waypoints.*.lat' => 'required|numeric|between:-90,90',
            'waypoints.*.lng' => 'required|numeric|between:-180,180',
            'option' => 'nullable|string|in:trafast,traoptimal,tracomfort,traavoidtoll,traavoidcaronly',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $waypoints = $request->input('waypoints', []);
        $options = [];
        if ($request->has('option')) {
            $options['option'] = $request->input('option');
        }

        $result = $this->mapsService->getDirections15(
            $request->input('start_lat'),
            $request->input('start_lng'),
            $request->input('goal_lat'),
            $request->input('goal_lng'),
            $waypoints,
            $options
        );

        if ($result === null) {
            return response()->json([
                'success' => false,
                'message' => 'Route not found'
            ], 404);
        }

        // Add waypoints count to response
        $result['waypoints_count'] = count($waypoints);

        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }
}
