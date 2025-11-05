<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTripDiaryRequest;
use App\Http\Requests\UpdateTripDiaryRequest;
use App\Http\Resources\TripDiaryResource;
use App\Models\Trip;
use App\Models\TripDiary;
use Illuminate\Http\Request;

class TripDiaryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *     path="/api/diaries",
     *     summary="List diary entries for authenticated user",
     *     tags={"Trip Diaries"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="trip_id",
     *         in="query",
     *         description="Filter by trip ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="entry_date",
     *         in="query",
     *         description="Filter by entry date (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2024-01-15")
     *     ),
     *     @OA\Parameter(
     *         name="mood",
     *         in="query",
     *         description="Filter by mood",
     *         required=false,
     *         @OA\Schema(type="string", enum={"happy", "excited", "tired", "sad", "neutral"})
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
     *         description="List of diary entries ordered by date (newest first)",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/TripDiary")),
     *             @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized")
     * )
     */
    public function index(Request $request)
    {
        $query = TripDiary::query()
            ->where('user_id', auth()->id())
            ->with(['trip', 'user']);

        // Filter by trip_id
        if ($request->has('trip_id')) {
            $query->where('trip_id', $request->input('trip_id'));
        }

        // Filter by entry_date
        if ($request->has('entry_date')) {
            $query->whereDate('entry_date', $request->input('entry_date'));
        }

        // Filter by mood
        if ($request->has('mood')) {
            $query->where('mood', $request->input('mood'));
        }

        $diaries = $query->orderBy('entry_date', 'desc')
            ->paginate(15);

        return TripDiaryResource::collection($diaries);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @OA\Post(
     *     path="/api/diaries",
     *     summary="Create a diary entry for a trip",
     *     tags={"Trip Diaries"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"trip_id", "entry_date", "text"},
     *             @OA\Property(property="trip_id", type="integer", example=5),
     *             @OA\Property(property="entry_date", type="string", format="date", example="2024-01-15"),
     *             @OA\Property(property="text", type="string", example="Today we visited the Tokyo Tower. The view was amazing!"),
     *             @OA\Property(property="mood", type="string", enum={"happy", "excited", "tired", "sad", "neutral"}, example="happy")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Diary entry created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/TripDiary")
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function store(StoreTripDiaryRequest $request)
    {
        // Check if user owns the trip
        $trip = Trip::findOrFail($request->input('trip_id'));
        $this->authorize('view', $trip);

        $diary = TripDiary::create([
            'trip_id' => $request->input('trip_id'),
            'user_id' => auth()->id(),
            'entry_date' => $request->input('entry_date'),
            'text' => $request->input('text'),
            'mood' => $request->input('mood'),
        ]);

        $diary->load(['trip', 'user']);

        return new TripDiaryResource($diary);
    }

    /**
     * Display the specified resource.
     *
     * @OA\Get(
     *     path="/api/diaries/{diary}",
     *     summary="View a single diary entry",
     *     tags={"Trip Diaries"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="diary",
     *         in="path",
     *         description="Diary ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Diary entry details with trip and user relationships",
     *         @OA\JsonContent(ref="#/components/schemas/TripDiary")
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function show(TripDiary $diary)
    {
        $this->authorize('view', $diary);

        $diary->load(['trip', 'user']);

        return new TripDiaryResource($diary);
    }

    /**
     * Update the specified resource in storage.
     *
     * @OA\Patch(
     *     path="/api/diaries/{diary}",
     *     summary="Update diary entry text or mood (owner only)",
     *     tags={"Trip Diaries"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="diary",
     *         in="path",
     *         description="Diary ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="text", type="string", example="Updated diary entry text"),
     *             @OA\Property(property="mood", type="string", enum={"happy", "excited", "tired", "sad", "neutral"}, example="excited")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Diary entry updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/TripDiary")
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function update(UpdateTripDiaryRequest $request, TripDiary $diary)
    {
        $this->authorize('update', $diary);

        $diary->update($request->only(['text', 'mood']));

        $diary->load(['trip', 'user']);

        return new TripDiaryResource($diary);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @OA\Delete(
     *     path="/api/diaries/{diary}",
     *     summary="Delete a diary entry (owner only)",
     *     tags={"Trip Diaries"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="diary",
     *         in="path",
     *         description="Diary ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Diary entry deleted successfully"
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function destroy(TripDiary $diary)
    {
        $this->authorize('delete', $diary);

        $diary->delete();

        return response()->noContent();
    }
}
