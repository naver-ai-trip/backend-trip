<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreItineraryItemRequest;
use App\Http\Requests\UpdateItineraryItemRequest;
use App\Http\Resources\ItineraryItemResource;
use App\Models\ItineraryItem;
use App\Models\Trip;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ItineraryItemController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *     path="/api/itinerary-items",
     *     summary="List itinerary items with day/time ordering",
     *     tags={"Itinerary Items"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="trip_id",
     *         in="query",
     *         description="Filter by trip ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="day_number",
     *         in="query",
     *         description="Filter by day number (1-based)",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, example=2)
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
     *         description="List of itinerary items ordered by day_number and start_time",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/ItineraryItem")),
     *             @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized")
     * )
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = ItineraryItem::query()
            ->with(['trip', 'place']);

        // Filter by trip_id
        if ($request->has('trip_id')) {
            $query->forTrip($request->input('trip_id'));
        }

        // Filter by day_number
        if ($request->has('day_number')) {
            $query->forDay($request->input('day_number'));
        }

        // Order by day and time
        $query->ordered();

        $items = $query->paginate(15);

        return ItineraryItemResource::collection($items);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @OA\Post(
     *     path="/api/itinerary-items",
     *     summary="Create an itinerary item for a trip (trip owner only)",
     *     tags={"Itinerary Items"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"trip_id", "title", "day_number"},
     *             @OA\Property(property="trip_id", type="integer", example=5),
     *             @OA\Property(property="title", type="string", example="Visit Tokyo Tower"),
     *             @OA\Property(property="day_number", type="integer", minimum=1, example=2),
     *             @OA\Property(property="start_time", type="string", format="time", example="09:00:00", description="H:i:s format (nullable)"),
     *             @OA\Property(property="end_time", type="string", format="time", example="11:00:00", description="H:i:s format (nullable)"),
     *             @OA\Property(property="place_id", type="integer", example=10, description="Optional place association"),
     *             @OA\Property(property="description", type="string", example="Enjoy panoramic views of Tokyo")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Itinerary item created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/ItineraryItem")
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function store(StoreItineraryItemRequest $request): ItineraryItemResource
    {
        $trip = Trip::findOrFail($request->input('trip_id'));
        
        // Check if user owns the trip
        $this->authorize('update', $trip);

        $item = ItineraryItem::create($request->validated());

        return new ItineraryItemResource($item->load(['trip', 'place']));
    }

    /**
     * Display the specified resource.
     *
     * @OA\Get(
     *     path="/api/itinerary-items/{itineraryItem}",
     *     summary="View a single itinerary item",
     *     tags={"Itinerary Items"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="itineraryItem",
     *         in="path",
     *         description="Itinerary Item ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Itinerary item details with trip and place relationships",
     *         @OA\JsonContent(ref="#/components/schemas/ItineraryItem")
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function show(ItineraryItem $itineraryItem): ItineraryItemResource
    {
        $itineraryItem->load(['trip', 'place']);
        
        $this->authorize('view', $itineraryItem);

        return new ItineraryItemResource($itineraryItem);
    }

    /**
     * Update the specified resource in storage.
     *
     * @OA\Patch(
     *     path="/api/itinerary-items/{itineraryItem}",
     *     summary="Update itinerary item (trip owner only)",
     *     tags={"Itinerary Items"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="itineraryItem",
     *         in="path",
     *         description="Itinerary Item ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string", example="Updated activity title"),
     *             @OA\Property(property="day_number", type="integer", minimum=1, example=3),
     *             @OA\Property(property="start_time", type="string", format="time", example="10:30:00"),
     *             @OA\Property(property="end_time", type="string", format="time", example="12:30:00"),
     *             @OA\Property(property="place_id", type="integer", example=15),
     *             @OA\Property(property="description", type="string", example="Updated description")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Itinerary item updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/ItineraryItem")
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function update(UpdateItineraryItemRequest $request, ItineraryItem $itineraryItem): ItineraryItemResource
    {
        $itineraryItem->load('trip');
        
        $this->authorize('update', $itineraryItem);

        $itineraryItem->update($request->validated());

        return new ItineraryItemResource($itineraryItem->load(['trip', 'place']));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @OA\Delete(
     *     path="/api/itinerary-items/{itineraryItem}",
     *     summary="Delete an itinerary item (trip owner only)",
     *     tags={"Itinerary Items"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="itineraryItem",
     *         in="path",
     *         description="Itinerary Item ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Itinerary item deleted successfully"
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function destroy(ItineraryItem $itineraryItem): JsonResponse
    {
        $itineraryItem->load('trip');
        
        $this->authorize('delete', $itineraryItem);

        $itineraryItem->delete();

        return response()->json(null, 204);
    }
}
