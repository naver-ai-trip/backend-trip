<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreShareRequest;
use App\Http\Resources\ShareResource;
use App\Http\Resources\TripResource;
use App\Models\Share;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ShareController extends Controller
{
    /**
     * Display a listing of shares for a trip.
     *
     * @OA\Get(
     *     path="/api/trips/{trip}/shares",
     *     summary="List share links for a trip (trip owner only)",
     *     tags={"Trip Shares"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="trip",
     *         in="path",
     *         description="Trip ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="permission",
     *         in="query",
     *         description="Filter by permission level",
     *         required=false,
     *         @OA\Schema(type="string", enum={"viewer", "editor"})
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
     *         description="List of share links with auto-generated tokens",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Share")),
     *             @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function index(Request $request, Trip $trip): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [Share::class, $trip]);

        $query = Share::where('trip_id', $trip->id)
            ->with('user');

        // Filter by permission if provided
        if ($request->has('permission')) {
            $query->forPermission($request->input('permission'));
        }

        $shares = $query->paginate(15);

        return ShareResource::collection($shares);
    }

    /**
     * Store a newly created share in storage.
     *
     * @OA\Post(
     *     path="/api/trips/{trip}/shares",
     *     summary="Create a share link for a trip (trip owner only)",
     *     tags={"Trip Shares"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="trip",
     *         in="path",
     *         description="Trip ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="permission", type="string", enum={"viewer", "editor"}, example="viewer", description="Default: viewer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Share link created with auto-generated 32-character token",
     *         @OA\JsonContent(ref="#/components/schemas/Share")
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function store(StoreShareRequest $request, Trip $trip): ShareResource
    {
        $this->authorize('create', [Share::class, $trip]);

        $share = Share::create([
            'trip_id' => $trip->id,
            'user_id' => auth()->id(),
            'permission' => $request->input('permission', 'viewer'),
        ]);

        return new ShareResource($share);
    }

    /**
     * Display the trip via share token.
     *
     * @OA\Get(
     *     path="/api/shares/{token}",
     *     summary="Access a shared trip via token (public endpoint)",
     *     tags={"Trip Shares"},
     *     @OA\Parameter(
     *         name="token",
     *         in="path",
     *         description="32-character share token",
     *         required=true,
     *         @OA\Schema(type="string", example="abc123xyz456...")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Trip details accessible via share link",
     *         @OA\JsonContent(ref="#/components/schemas/Trip")
     *     ),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function show(string $token): TripResource
    {
        $share = Share::forToken($token)->firstOrFail();
        
        $trip = Trip::findOrFail($share->trip_id);

        return new TripResource($trip);
    }

    /**
     * Remove the specified share from storage.
     *
     * @OA\Delete(
     *     path="/api/shares/{share}",
     *     summary="Revoke a share link (creator only)",
     *     tags={"Trip Shares"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="share",
     *         in="path",
     *         description="Share ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Share link revoked successfully"
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function destroy(Share $share): Response
    {
        $this->authorize('delete', $share);

        $share->delete();

        return response()->noContent();
    }
}
