<?php

namespace App\Http\Controllers;

use App\Services\Naver\SearchTrendService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Search Trends",
 *     description="NAVER DataLab Search Trend API - Analyze keyword search trends, demographics, and seasonal patterns"
 * )
 */
class SearchTrendController extends Controller
{
    public function __construct(
        private readonly SearchTrendService $searchTrendService
    ) {}

    /**
     * @OA\Post(
     *     path="/api/search-trends/keywords",
     *     operationId="getKeywordTrends",
     *     tags={"Search Trends"},
     *     summary="Get keyword search trends over time",
     *     description="Analyze search volume trends for one or more keywords with optional demographic and device filters",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"keywords", "start_date", "end_date"},
     *             @OA\Property(
     *                 property="keywords",
     *                 type="array",
     *                 description="Array of keywords to analyze (max 20 keywords)",
     *                 example={"제주도 여행", "부산 여행"},
     *                 @OA\Items(type="string")
     *             ),
     *             @OA\Property(
     *                 property="start_date",
     *                 type="string",
     *                 format="date",
     *                 description="Start date (YYYY-MM-DD)",
     *                 example="2024-01-01"
     *             ),
     *             @OA\Property(
     *                 property="end_date",
     *                 type="string",
     *                 format="date",
     *                 description="End date (YYYY-MM-DD)",
     *                 example="2024-12-31"
     *             ),
     *             @OA\Property(
     *                 property="time_unit",
     *                 type="string",
     *                 enum={"date", "week", "month"},
     *                 description="Time aggregation unit",
     *                 example="month"
     *             ),
     *             @OA\Property(
     *                 property="device",
     *                 type="string",
     *                 enum={"", "pc", "mo"},
     *                 description="Device filter: empty string or omit = all devices, 'pc' = PC only, 'mo' = mobile only",
     *                 example=""
     *             ),
     *             @OA\Property(
     *                 property="gender",
     *                 type="string",
     *                 enum={"m", "f"},
     *                 description="Gender filter (optional)",
     *                 example="f"
     *             ),
     *             @OA\Property(
     *                 property="ages",
     *                 type="array",
     *                 description="Age group filters: 1=0-12, 2=13-18, 3=19-24, 4=25-29, 5=30-34, 6=35-39, 7=40-44, 8=45-49, 9=50-54, 10=55-59, 11=60+",
     *                 example={"3", "4", "5"},
     *                 @OA\Items(type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful trend data retrieval",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="startDate", type="string", example="2024-01-01"),
     *                 @OA\Property(property="endDate", type="string", example="2024-12-31"),
     *                 @OA\Property(property="timeUnit", type="string", example="month"),
     *                 @OA\Property(
     *                     property="results",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="title", type="string", example="제주도 여행"),
     *                         @OA\Property(
     *                             property="keywords",
     *                             type="array",
     *                             @OA\Items(type="string", example="제주도 여행")
     *                         ),
     *                         @OA\Property(
     *                             property="data",
     *                             type="array",
     *                             @OA\Items(
     *                                 @OA\Property(property="period", type="string", example="2024-01"),
     *                                 @OA\Property(property="ratio", type="number", format="float", example=85.5)
     *                             )
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="API error")
     * )
     */
    public function getKeywordTrends(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'keywords' => 'required|array|min:1|max:20',
            'keywords.*' => 'required|string|max:100',
            'start_date' => 'required|date|date_format:Y-m-d',
            'end_date' => 'required|date|date_format:Y-m-d|after_or_equal:start_date',
            'time_unit' => 'sometimes|string|in:date,week,month',
            'device' => 'sometimes|string|in:,pc,mo',
            'gender' => 'sometimes|string|in:m,f',
            'ages' => 'sometimes|array',
            'ages.*' => 'string|in:1,2,3,4,5,6,7,8,9,10,11',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $data = $this->searchTrendService->getKeywordTrends(
                $request->input('keywords'),
                $request->input('start_date'),
                $request->input('end_date'),
                $request->input('time_unit', 'date'),
                $request->input('device', ''),
                $request->input('gender'),
                $request->input('ages', [])
            );

            if ($data === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Search Trend API is disabled',
                ], 503);
            }

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch trend data: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/search-trends/compare",
     *     operationId="compareKeywords",
     *     tags={"Search Trends"},
     *     summary="Compare multiple keyword groups",
     *     description="Compare search trends for up to 5 different keyword groups simultaneously",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"keyword_groups", "start_date", "end_date"},
     *             @OA\Property(
     *                 property="keyword_groups",
     *                 type="array",
     *                 description="Array of keyword arrays (max 5 groups)",
     *                 example={{"제주도 여행"}, {"부산 여행"}, {"강릉 여행"}},
     *                 @OA\Items(
     *                     type="array",
     *                     @OA\Items(type="string")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="start_date",
     *                 type="string",
     *                 format="date",
     *                 description="Start date (YYYY-MM-DD)",
     *                 example="2024-01-01"
     *             ),
     *             @OA\Property(
     *                 property="end_date",
     *                 type="string",
     *                 format="date",
     *                 description="End date (YYYY-MM-DD)",
     *                 example="2024-12-31"
     *             ),
     *             @OA\Property(
     *                 property="time_unit",
     *                 type="string",
     *                 enum={"date", "week", "month"},
     *                 description="Time aggregation unit",
     *                 example="month"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful comparison data retrieval",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 description="Comparison results for all keyword groups"
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="API error")
     * )
     */
    public function compareKeywords(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'keyword_groups' => 'required|array|min:1|max:5',
            'keyword_groups.*' => 'required|array|min:1',
            'keyword_groups.*.*' => 'required|string|max:100',
            'start_date' => 'required|date|date_format:Y-m-d',
            'end_date' => 'required|date|date_format:Y-m-d|after_or_equal:start_date',
            'time_unit' => 'sometimes|string|in:date,week,month',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $data = $this->searchTrendService->compareKeywords(
                $request->input('keyword_groups'),
                $request->input('start_date'),
                $request->input('end_date'),
                $request->input('time_unit', 'date')
            );

            if ($data === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Search Trend API is disabled',
                ], 503);
            }

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to compare keywords: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/search-trends/demographics",
     *     operationId="getAgeGenderTrends",
     *     tags={"Search Trends"},
     *     summary="Get age and gender demographic trends",
     *     description="Analyze search trends broken down by age groups and gender",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"keywords", "start_date", "end_date"},
     *             @OA\Property(
     *                 property="keywords",
     *                 type="array",
     *                 description="Keywords to analyze",
     *                 example={"제주도 여행"},
     *                 @OA\Items(type="string")
     *             ),
     *             @OA\Property(
     *                 property="start_date",
     *                 type="string",
     *                 format="date",
     *                 example="2024-01-01"
     *             ),
     *             @OA\Property(
     *                 property="end_date",
     *                 type="string",
     *                 format="date",
     *                 example="2024-12-31"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Demographic breakdown retrieved",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="API error")
     * )
     */
    public function getAgeGenderTrends(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'keywords' => 'required|array|min:1',
            'keywords.*' => 'required|string|max:100',
            'start_date' => 'required|date|date_format:Y-m-d',
            'end_date' => 'required|date|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $data = $this->searchTrendService->getAgeGenderTrends(
                $request->input('keywords'),
                $request->input('start_date'),
                $request->input('end_date')
            );

            if ($data === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Search Trend API is disabled',
                ], 503);
            }

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch demographic data: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/search-trends/devices",
     *     operationId="getDeviceTrends",
     *     tags={"Search Trends"},
     *     summary="Get device usage trends",
     *     description="Analyze search trends broken down by device type (mobile vs PC)",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"keywords", "start_date", "end_date"},
     *             @OA\Property(
     *                 property="keywords",
     *                 type="array",
     *                 description="Keywords to analyze",
     *                 example={"제주도 여행"},
     *                 @OA\Items(type="string")
     *             ),
     *             @OA\Property(
     *                 property="start_date",
     *                 type="string",
     *                 format="date",
     *                 example="2024-01-01"
     *             ),
     *             @OA\Property(
     *                 property="end_date",
     *                 type="string",
     *                 format="date",
     *                 example="2024-12-31"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Device breakdown retrieved",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="API error")
     * )
     */
    public function getDeviceTrends(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'keywords' => 'required|array|min:1',
            'keywords.*' => 'required|string|max:100',
            'start_date' => 'required|date|date_format:Y-m-d',
            'end_date' => 'required|date|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $data = $this->searchTrendService->getDeviceTrends(
                $request->input('keywords'),
                $request->input('start_date'),
                $request->input('end_date')
            );

            if ($data === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Search Trend API is disabled',
                ], 503);
            }

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch device data: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/search-trends/destination-popularity",
     *     operationId="analyzeDestinationPopularity",
     *     tags={"Search Trends"},
     *     summary="Analyze travel destination popularity",
     *     description="Analyze destination search trends with peak period detection",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"destination", "start_date", "end_date"},
     *             @OA\Property(
     *                 property="destination",
     *                 type="string",
     *                 description="Destination keyword",
     *                 example="제주도 여행"
     *             ),
     *             @OA\Property(
     *                 property="start_date",
     *                 type="string",
     *                 format="date",
     *                 example="2024-01-01"
     *             ),
     *             @OA\Property(
     *                 property="end_date",
     *                 type="string",
     *                 format="date",
     *                 example="2024-12-31"
     *             ),
     *             @OA\Property(
     *                 property="time_unit",
     *                 type="string",
     *                 enum={"date", "week", "month"},
     *                 example="month"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Destination popularity analysis retrieved",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="keyword", type="string", example="제주도 여행"),
     *                 @OA\Property(property="peak_period", type="string", example="2024-08"),
     *                 @OA\Property(property="peak_ratio", type="number", format="float", example=95.5),
     *                 @OA\Property(property="total_data_points", type="integer", example=12)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="API error")
     * )
     */
    public function analyzeDestinationPopularity(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'destination' => 'required|string|max:100',
            'start_date' => 'required|date|date_format:Y-m-d',
            'end_date' => 'required|date|date_format:Y-m-d|after_or_equal:start_date',
            'time_unit' => 'sometimes|string|in:date,week,month',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $data = $this->searchTrendService->analyzeDestinationPopularity(
                $request->input('destination'),
                $request->input('start_date'),
                $request->input('end_date'),
                $request->input('time_unit', 'month')
            );

            if ($data === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'No trend data available for this destination',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to analyze destination: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/search-trends/seasonal-insights",
     *     operationId="getSeasonalInsights",
     *     tags={"Search Trends"},
     *     summary="Get seasonal travel insights",
     *     description="Analyze seasonal patterns with summer/winter peak detection",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"keyword"},
     *             @OA\Property(
     *                 property="keyword",
     *                 type="string",
     *                 description="Travel keyword to analyze",
     *                 example="제주도 여행"
     *             ),
     *             @OA\Property(
     *                 property="months",
     *                 type="integer",
     *                 description="Number of months to analyze (default: 12)",
     *                 example=12,
     *                 minimum=1,
     *                 maximum=36
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Seasonal insights retrieved",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="keyword", type="string", example="제주도 여행"),
     *                 @OA\Property(
     *                     property="summer_peak",
     *                     type="object",
     *                     @OA\Property(property="period", type="string", example="2024-08"),
     *                     @OA\Property(property="ratio", type="number", example=95.5)
     *                 ),
     *                 @OA\Property(
     *                     property="winter_peak",
     *                     type="object",
     *                     @OA\Property(property="period", type="string", example="2024-01"),
     *                     @OA\Property(property="ratio", type="number", example=75.2)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="API error")
     * )
     */
    public function getSeasonalInsights(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'keyword' => 'required|string|max:100',
            'months' => 'sometimes|integer|min:1|max:36',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $data = $this->searchTrendService->getSeasonalInsights(
                $request->input('keyword'),
                $request->input('months', 12)
            );

            if ($data === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'No seasonal data available',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get seasonal insights: ' . $e->getMessage(),
            ], 500);
        }
    }
}
