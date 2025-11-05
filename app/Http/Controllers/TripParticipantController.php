<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTripParticipantRequest;
use App\Http\Requests\UpdateTripParticipantRequest;
use App\Http\Resources\TripParticipantResource;
use App\Models\Trip;
use App\Models\TripParticipant;
use Illuminate\Http\Request;

class TripParticipantController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *     path="/api/trips/{trip}/participants",
     *     summary="List participants of a trip",
     *     tags={"Trip Participants"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="trip",
     *         in="path",
     *         description="Trip ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="role",
     *         in="query",
     *         description="Filter by role",
     *         required=false,
     *         @OA\Schema(type="string", enum={"owner", "editor", "viewer"})
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
     *         description="List of trip participants with user relationships",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/TripParticipant")),
     *             @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function index(Request $request, Trip $trip)
    {
        $this->authorize('viewAny', [TripParticipant::class, $trip]);

        $query = $trip->participants()->with('user');

        // Filter by role
        if ($request->has('role')) {
            $query->where('role', $request->input('role'));
        }

        $participants = $query->paginate(15);

        return TripParticipantResource::collection($participants);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @OA\Post(
     *     path="/api/trips/{trip}/participants",
     *     summary="Invite a participant to a trip (owner only)",
     *     tags={"Trip Participants"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="trip",
     *         in="path",
     *         description="Trip ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"user_id", "role"},
     *             @OA\Property(property="user_id", type="integer", example=5),
     *             @OA\Property(property="role", type="string", enum={"owner", "editor", "viewer"}, example="editor")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Participant invited successfully",
     *         @OA\JsonContent(ref="#/components/schemas/TripParticipant")
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function store(StoreTripParticipantRequest $request, Trip $trip)
    {
        $this->authorize('create', [TripParticipant::class, $trip]);

        $participant = TripParticipant::create([
            'trip_id' => $trip->id,
            'user_id' => $request->input('user_id'),
            'role' => $request->input('role'),
        ]);

        $participant->load('user');

        return new TripParticipantResource($participant);
    }

    /**
     * Display the specified resource.
     *
     * @OA\Get(
     *     path="/api/trips/{trip}/participants/{participant}",
     *     summary="View a single participant",
     *     tags={"Trip Participants"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="trip",
     *         in="path",
     *         description="Trip ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="participant",
     *         in="path",
     *         description="Participant ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Participant details with user relationship",
     *         @OA\JsonContent(ref="#/components/schemas/TripParticipant")
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function show(Trip $trip, TripParticipant $participant)
    {
        $this->authorize('view', $participant);

        $participant->load('user');

        return new TripParticipantResource($participant);
    }

    /**
     * Update the specified resource in storage.
     *
     * @OA\Patch(
     *     path="/api/trips/{trip}/participants/{participant}",
     *     summary="Update participant role (owner only)",
     *     tags={"Trip Participants"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="trip",
     *         in="path",
     *         description="Trip ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="participant",
     *         in="path",
     *         description="Participant ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"role"},
     *             @OA\Property(property="role", type="string", enum={"owner", "editor", "viewer"}, example="viewer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Participant role updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/TripParticipant")
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function update(UpdateTripParticipantRequest $request, Trip $trip, TripParticipant $participant)
    {
        $this->authorize('update', $participant);

        $participant->update([
            'role' => $request->input('role'),
        ]);

        $participant->load('user');

        return new TripParticipantResource($participant);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @OA\Delete(
     *     path="/api/trips/{trip}/participants/{participant}",
     *     summary="Remove a participant from trip",
     *     tags={"Trip Participants"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="trip",
     *         in="path",
     *         description="Trip ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="participant",
     *         in="path",
     *         description="Participant ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Participant removed successfully"
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function destroy(Trip $trip, TripParticipant $participant)
    {
        $this->authorize('delete', $participant);

        $participant->delete();

        return response()->noContent();
    }
}
