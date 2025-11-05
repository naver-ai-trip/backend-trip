<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMapCheckpointRequest;
use App\Http\Requests\UpdateMapCheckpointRequest;
use App\Http\Resources\MapCheckpointResource;
use App\Models\MapCheckpoint;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class MapCheckpointController extends Controller
{
    /**
     * Display a listing of checkpoints.
     *
     * @OA\Get(
     *     path="/api/checkpoints",
     *     summary="List map checkpoints",
     *     tags={"Map Checkpoints"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="trip_id",
     *         in="query",
     *         description="Filter by trip ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="checked_in",
     *         in="query",
     *         description="Filter by check-in status",
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Results per page",
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Checkpoint list",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/MapCheckpoint")),
     *             @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized")
     * )
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = MapCheckpoint::query();

        // Filter by trip_id (required)
        if ($request->has('trip_id')) {
            $query->forTrip($request->input('trip_id'));
        }

        // Filter by checked_in status
        if ($request->has('checked_in') && $request->boolean('checked_in')) {
            $query->checkedIn();
        }

        // Load relationships
        $query->with(['place']);

        // Paginate results
        $perPage = $request->input('per_page', 15);
        $checkpoints = $query->paginate($perPage);

        return MapCheckpointResource::collection($checkpoints);
    }

    /**
     * Store a newly created checkpoint.
     *
     * @OA\Post(
     *     path="/api/checkpoints",
     *     summary="Create a new checkpoint",
     *     tags={"Map Checkpoints"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"trip_id", "title", "lat", "lng"},
     *             @OA\Property(property="trip_id", type="integer", example=1),
     *             @OA\Property(property="place_id", type="integer", nullable=true, example=1),
     *             @OA\Property(property="title", type="string", example="Shibuya Crossing"),
     *             @OA\Property(property="lat", type="number", format="double", example=35.6595),
     *             @OA\Property(property="lng", type="number", format="double", example=139.7004),
     *             @OA\Property(property="note", type="string", nullable=true, example="Best photo spot"),
     *             @OA\Property(property="checked_in_at", type="string", format="date-time", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Checkpoint created",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/MapCheckpoint")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function store(StoreMapCheckpointRequest $request): MapCheckpointResource
    {
        $checkpoint = MapCheckpoint::create([
            'trip_id' => $request->input('trip_id'),
            'user_id' => $request->user()->id,
            'place_id' => $request->input('place_id'),
            'title' => $request->input('title'),
            'lat' => $request->input('lat'),
            'lng' => $request->input('lng'),
            'note' => $request->input('note'),
            'checked_in_at' => $request->input('checked_in_at'),
        ]);

        return new MapCheckpointResource($checkpoint);
    }

    /**
     * Display the specified checkpoint.
     *
     * @OA\Get(
     *     path="/api/checkpoints/{checkpoint}",
     *     summary="Get checkpoint details",
     *     tags={"Map Checkpoints"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="checkpoint",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Checkpoint details",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/MapCheckpoint")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function show(MapCheckpoint $checkpoint): MapCheckpointResource
    {
        // Load trip relationship for authorization
        $checkpoint->load('trip', 'place');
        
        $this->authorize('view', $checkpoint);

        return new MapCheckpointResource($checkpoint);
    }

    /**
     * Update the specified checkpoint.
     *
     * @OA\Patch(
     *     path="/api/checkpoints/{checkpoint}",
     *     summary="Update a checkpoint",
     *     tags={"Map Checkpoints"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="checkpoint",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="trip_id", type="integer"),
     *             @OA\Property(property="place_id", type="integer", nullable=true),
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="lat", type="number", format="double"),
     *             @OA\Property(property="lng", type="number", format="double"),
     *             @OA\Property(property="note", type="string", nullable=true),
     *             @OA\Property(property="checked_in_at", type="string", format="date-time", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Checkpoint updated",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/MapCheckpoint")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function update(UpdateMapCheckpointRequest $request, MapCheckpoint $checkpoint): MapCheckpointResource
    {
        // Load trip relationship for authorization
        $checkpoint->load('trip');
        
        $this->authorize('update', $checkpoint);

        $checkpoint->update($request->only([
            'trip_id',
            'place_id',
            'title',
            'lat',
            'lng',
            'note',
            'checked_in_at',
        ]));

        return new MapCheckpointResource($checkpoint);
    }

    /**
     * Remove the specified checkpoint.
     *
     * @OA\Delete(
     *     path="/api/checkpoints/{checkpoint}",
     *     summary="Delete a checkpoint",
     *     tags={"Map Checkpoints"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="checkpoint",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=204, description="Checkpoint deleted"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function destroy(MapCheckpoint $checkpoint): Response
    {
        // Load trip relationship for authorization
        $checkpoint->load('trip');
        
        $this->authorize('delete', $checkpoint);

        $checkpoint->delete();

        return response()->noContent();
    }
}
