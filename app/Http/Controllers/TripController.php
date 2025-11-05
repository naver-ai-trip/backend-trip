<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTripRequest;
use App\Http\Requests\UpdateTripRequest;
use App\Http\Resources\TripResource;
use App\Models\Trip;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TripController extends Controller
{
    /**
     * Display a listing of the user's trips.
     * 
     * @OA\Get(
     *     path="/api/trips",
     *     summary="List user's trips",
     *     tags={"Trips"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by trip status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"planning", "ongoing", "completed"})
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Results per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Trip")),
     *             @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized")
     * )
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Trip::where('user_id', $request->user()->id);

        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $perPage = $request->input('per_page', 15);
        $trips = $query->latest()->paginate($perPage);

        return TripResource::collection($trips);
    }

    /**
     * Store a newly created trip.
     *
     * @OA\Post(
     *     path="/api/trips",
     *     summary="Create a new trip",
     *     tags={"Trips"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title", "destination_country", "destination_city", "start_date", "end_date"},
     *             @OA\Property(property="title", type="string", example="Tokyo Adventure"),
     *             @OA\Property(property="destination_country", type="string", example="Japan"),
     *             @OA\Property(property="destination_city", type="string", example="Tokyo"),
     *             @OA\Property(property="start_date", type="string", format="date", example="2025-12-01"),
     *             @OA\Property(property="end_date", type="string", format="date", example="2025-12-07"),
     *             @OA\Property(property="status", type="string", enum={"planning", "ongoing", "completed"}, example="planning"),
     *             @OA\Property(property="is_group", type="boolean", example=false),
     *             @OA\Property(property="progress", type="string", nullable=true, example="Planning itinerary")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Trip created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/Trip"),
     *             @OA\Property(property="message", type="string", example="Trip created successfully")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function store(StoreTripRequest $request): JsonResponse
    {
        $trip = Trip::create([
            'user_id' => $request->user()->id,
            'title' => $request->input('title'),
            'destination_country' => $request->input('destination_country'),
            'destination_city' => $request->input('destination_city'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'status' => $request->input('status', 'planning'),
            'is_group' => $request->input('is_group', false),
            'progress' => $request->input('progress'),
        ]);

        return response()->json([
            'data' => new TripResource($trip),
            'message' => 'Trip created successfully',
        ], 201);
    }

    /**
     * Display the specified trip.
     *
     * @OA\Get(
     *     path="/api/trips/{trip}",
     *     summary="Get trip details",
     *     tags={"Trips"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="trip",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/Trip")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function show(Trip $trip): JsonResponse
    {
        $this->authorize('view', $trip);

        return response()->json([
            'data' => new TripResource($trip),
        ]);
    }

    /**
     * Update the specified trip.
     *
     * @OA\Patch(
     *     path="/api/trips/{trip}",
     *     summary="Update a trip",
     *     tags={"Trips"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="trip",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string", example="Updated Tokyo Trip"),
     *             @OA\Property(property="destination_country", type="string", example="Japan"),
     *             @OA\Property(property="destination_city", type="string", example="Tokyo"),
     *             @OA\Property(property="start_date", type="string", format="date"),
     *             @OA\Property(property="end_date", type="string", format="date"),
     *             @OA\Property(property="status", type="string", enum={"planning", "ongoing", "completed"}),
     *             @OA\Property(property="is_group", type="boolean"),
     *             @OA\Property(property="progress", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Trip updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/Trip"),
     *             @OA\Property(property="message", type="string", example="Trip updated successfully")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function update(UpdateTripRequest $request, Trip $trip): JsonResponse
    {
        $this->authorize('update', $trip);

        $trip->update($request->only([
            'title',
            'destination_country',
            'destination_city',
            'start_date',
            'end_date',
            'status',
            'is_group',
            'progress',
        ]));

        return response()->json([
            'data' => new TripResource($trip),
            'message' => 'Trip updated successfully',
        ]);
    }

    /**
     * Remove the specified trip.
     *
     * @OA\Delete(
     *     path="/api/trips/{trip}",
     *     summary="Delete a trip",
     *     tags={"Trips"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="trip",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=204, description="Trip deleted successfully"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function destroy(Trip $trip): JsonResponse
    {
        $this->authorize('delete', $trip);

        $trip->delete();

        return response()->json(null, 204);
    }
}
