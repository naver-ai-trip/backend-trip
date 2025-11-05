<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreChecklistItemRequest;
use App\Http\Requests\UpdateChecklistItemRequest;
use App\Http\Resources\ChecklistItemResource;
use App\Models\ChecklistItem;
use App\Models\Trip;
use Illuminate\Http\Request;

class ChecklistItemController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *     path="/api/checklist-items",
     *     summary="List checklist items for authenticated user",
     *     tags={"Checklist Items"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="trip_id",
     *         in="query",
     *         description="Filter by trip ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="is_checked",
     *         in="query",
     *         description="Filter by checked status",
     *         required=false,
     *         @OA\Schema(type="boolean", example=false)
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
     *         description="List of checklist items ordered by newest first",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/ChecklistItem")),
     *             @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized")
     * )
     */
    public function index(Request $request)
    {
        $query = ChecklistItem::query()
            ->where('user_id', auth()->id())
            ->with(['trip']);

        // Filter by trip_id
        if ($request->has('trip_id')) {
            $query->where('trip_id', $request->input('trip_id'));
        }

        // Filter by checked status
        if ($request->has('is_checked')) {
            $query->where('is_checked', $request->boolean('is_checked'));
        }

        $items = $query->latest()
            ->paginate(15);

        return ChecklistItemResource::collection($items);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @OA\Post(
     *     path="/api/checklist-items",
     *     summary="Create a checklist item for a trip",
     *     tags={"Checklist Items"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"trip_id", "content"},
     *             @OA\Property(property="trip_id", type="integer", example=5),
     *             @OA\Property(property="content", type="string", example="Pack passport and tickets")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Checklist item created successfully (default is_checked: false)",
     *         @OA\JsonContent(ref="#/components/schemas/ChecklistItem")
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function store(StoreChecklistItemRequest $request)
    {
        // Check if user owns the trip
        $trip = Trip::findOrFail($request->input('trip_id'));
        $this->authorize('view', $trip);

        $item = ChecklistItem::create([
            'trip_id' => $request->input('trip_id'),
            'user_id' => auth()->id(),
            'content' => $request->input('content'),
            'is_checked' => false,
        ]);

        $item->load(['trip']);

        return new ChecklistItemResource($item);
    }

    /**
     * Display the specified resource.
     *
     * @OA\Get(
     *     path="/api/checklist-items/{checklistItem}",
     *     summary="View a single checklist item",
     *     tags={"Checklist Items"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="checklistItem",
     *         in="path",
     *         description="Checklist Item ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Checklist item details with trip relationship",
     *         @OA\JsonContent(ref="#/components/schemas/ChecklistItem")
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function show(ChecklistItem $checklistItem)
    {
        $this->authorize('view', $checklistItem);

        $checklistItem->load(['trip']);

        return new ChecklistItemResource($checklistItem);
    }

    /**
     * Update the specified resource in storage.
     *
     * @OA\Patch(
     *     path="/api/checklist-items/{checklistItem}",
     *     summary="Update checklist item content or toggle checked status (owner only)",
     *     tags={"Checklist Items"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="checklistItem",
     *         in="path",
     *         description="Checklist Item ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="content", type="string", example="Updated checklist item"),
     *             @OA\Property(property="is_checked", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Checklist item updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/ChecklistItem")
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function update(UpdateChecklistItemRequest $request, ChecklistItem $checklistItem)
    {
        $this->authorize('update', $checklistItem);

        $checklistItem->update($request->only(['content', 'is_checked']));

        $checklistItem->load(['trip']);

        return new ChecklistItemResource($checklistItem);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @OA\Delete(
     *     path="/api/checklist-items/{checklistItem}",
     *     summary="Delete a checklist item (owner only)",
     *     tags={"Checklist Items"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="checklistItem",
     *         in="path",
     *         description="Checklist Item ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Checklist item deleted successfully"
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function destroy(ChecklistItem $checklistItem)
    {
        $this->authorize('delete', $checklistItem);

        $checklistItem->delete();

        return response()->noContent();
    }
}
