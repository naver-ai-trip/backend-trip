<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchFlightOffersRequest;
use App\Services\Amadeus\FlightService;
use Illuminate\Http\JsonResponse;

/**
 * FlightController
 *
 * Provides endpoints for Amadeus Flight Offers Search.
 */
class FlightController extends Controller
{
    public function __construct(
        private FlightService $flightService,
    ) {}

    /**
     * Search for flight offers (Amadeus Flight Offers Search API).
     *
     * @OA\Post(
     *     path="/api/flights/search",
     *     summary="Search for flight offers",
     *     tags={"Flights"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"origin_location_code","destination_location_code","departure_date","adults"},
     *             @OA\Property(property="origin_location_code", type="string", example="ICN"),
     *             @OA\Property(property="destination_location_code", type="string", example="JFK"),
     *             @OA\Property(property="departure_date", type="string", format="date", example="2025-06-01"),
     *             @OA\Property(property="return_date", type="string", format="date", example="2025-06-10"),
     *             @OA\Property(property="adults", type="integer", example=1, minimum=1, maximum=9),
     *             @OA\Property(property="children", type="integer", example=1, minimum=0, maximum=9),
     *             @OA\Property(property="infants", type="integer", example=0, minimum=0, maximum=9),
     *             @OA\Property(property="travel_class", type="string", enum={"ECONOMY","PREMIUM_ECONOMY","BUSINESS","FIRST"}),
     *             @OA\Property(property="non_stop", type="boolean", example=false),
     *             @OA\Property(property="currency_code", type="string", example="USD"),
     *             @OA\Property(property="max_price", type="number", example=1500),
     *             @OA\Property(property="max", type="integer", example=50),
     *             @OA\Property(property="included_checked_bags_only", type="boolean", example=true),
     *             @OA\Property(property="one_way", type="boolean", example=false),
     *             @OA\Property(property="sources", type="array", @OA\Items(type="string"), example={"GDS"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Flight offers returned successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="total_offers", type="integer", example=15),
     *                 @OA\Property(property="origin", type="string", example="ICN"),
     *                 @OA\Property(property="destination", type="string", example="JFK"),
     *                 @OA\Property(property="departure_date", type="string", format="date", example="2025-06-01"),
     *                 @OA\Property(property="return_date", type="string", format="date", example="2025-06-10")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError"),
     *     @OA\Response(response=503, ref="#/components/responses/ServiceUnavailable")
     * )
     */
    public function searchOffers(SearchFlightOffersRequest $request): JsonResponse
    {
        $params = $this->buildAmadeusParameters($request);

        $results = $this->flightService->searchFlightOffers($params);

        if ($results === null) {
            return response()->json([
                'message' => 'Flight offers service is currently unavailable',
                'data' => [],
            ], 503);
        }

        return response()->json([
            'data' => $results,
            'meta' => [
                'total_offers' => count($results),
                'origin' => strtoupper($request->input('origin_location_code')),
                'destination' => strtoupper($request->input('destination_location_code')),
                'departure_date' => $request->input('departure_date'),
                'return_date' => $request->input('return_date'),
            ],
        ]);
    }

    /**
     * Build the Amadeus query payload from the validated request.
     */
    private function buildAmadeusParameters(SearchFlightOffersRequest $request): array
    {
        $payload = [
            'originLocationCode' => strtoupper($request->input('origin_location_code')),
            'destinationLocationCode' => strtoupper($request->input('destination_location_code')),
            'departureDate' => $request->input('departure_date'),
            'returnDate' => $request->input('return_date'),
            'adults' => $request->input('adults'),
            'children' => $request->input('children'),
            'infants' => $request->input('infants'),
            'travelClass' => $request->input('travel_class'),
            'currencyCode' => $request->input('currency_code'),
            'maxPrice' => $request->input('max_price'),
            'max' => $request->input('max'),
        ];

        if ($request->filled('non_stop')) {
            $payload['nonStop'] = $request->boolean('non_stop');
        }

        if ($request->filled('included_checked_bags_only')) {
            $payload['includedCheckedBagsOnly'] = $request->boolean('included_checked_bags_only');
        }

        if ($request->filled('one_way')) {
            $payload['oneWay'] = $request->boolean('one_way');
        }

        if ($request->filled('sources')) {
            $payload['sources'] = implode(',', $request->input('sources'));
        }

        return array_filter(
            $payload,
            fn($value) => $value !== null && $value !== ''
        );
    }
}
