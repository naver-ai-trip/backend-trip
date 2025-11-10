<?php

namespace Tests\Unit\Services\Naver;

use App\Services\Naver\NaverMapsService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NaverMapsServiceTest extends TestCase
{
    private NaverMapsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set test credentials
        config([
            'services.naver.maps.client_id' => 'test_client_id',
            'services.naver.maps.client_secret' => 'test_client_secret',
            'services.naver.maps.base_url' => 'https://naveropenapi.apigw.ntruss.com',
            'services.naver.maps.enabled' => true,
        ]);

        $this->service = new NaverMapsService();
    }

    /** @test */
    public function it_can_check_if_service_is_enabled()
    {
        $this->assertTrue($this->service->isEnabled());

        // Test with disabled service
        config(['services.naver.maps.enabled' => false]);
        $service = new NaverMapsService();
        $this->assertFalse($service->isEnabled());
    }

    /** @test */
    public function it_can_geocode_address_to_coordinates()
    {
        Http::fake([
            '*/map-geocode/v2/geocode*' => Http::response([
                'status' => 'OK',
                'meta' => ['totalCount' => 1, 'page' => 1, 'count' => 1],
                'addresses' => [
                    [
                        'roadAddress' => '서울특별시 강남구 테헤란로 152',
                        'jibunAddress' => '서울특별시 강남구 역삼동 737',
                        'englishAddress' => '152, Teheran-ro, Gangnam-gu, Seoul, Republic of Korea',
                        'addressElements' => [],
                        'x' => '127.0357270',
                        'y' => '37.4999940',
                        'distance' => 0.0,
                    ],
                ],
                'errorMessage' => '',
            ], 200),
        ]);

        $result = $this->service->geocode('강남역');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('address', $result);
        $this->assertArrayHasKey('roadAddress', $result);
        $this->assertArrayHasKey('latitude', $result);
        $this->assertArrayHasKey('longitude', $result);
        $this->assertEquals('서울특별시 강남구 역삼동 737', $result['address']);
        $this->assertEquals('서울특별시 강남구 테헤란로 152', $result['roadAddress']);
        $this->assertEquals(37.4999940, $result['latitude']);
        $this->assertEquals(127.0357270, $result['longitude']);

        // Verify request was made with correct params
        Http::assertSent(function ($request) {
            return $request->hasHeader('X-NCP-APIGW-API-KEY-ID', 'test_client_id') &&
                   $request->hasHeader('X-NCP-APIGW-API-KEY', 'test_client_secret') &&
                   str_contains($request->url(), 'map-geocode/v2/geocode') &&
                   $request['query'] === '강남역';
        });
    }

    /** @test */
    public function it_returns_null_when_geocode_finds_no_results()
    {
        Http::fake([
            '*/map-geocode/v2/geocode*' => Http::response([
                'status' => 'OK',
                'meta' => ['totalCount' => 0, 'page' => 1, 'count' => 0],
                'addresses' => [],
                'errorMessage' => '',
            ], 200),
        ]);

        $result = $this->service->geocode('nonexistent location xyz123');

        $this->assertNull($result);
    }

    /** @test */
    public function it_can_reverse_geocode_coordinates_to_address()
    {
        Http::fake([
            '*/map-reversegeocode/v2/gc*' => Http::response([
                'status' => [
                    'code' => 0,
                    'name' => 'ok',
                    'message' => 'done',
                ],
                'results' => [
                    [
                        'name' => 'roadaddr',
                        'code' => [
                            'id' => '1168010800',
                            'type' => 'L',
                            'mappingId' => '09168115',
                        ],
                        'region' => [
                            'area0' => ['name' => 'kr'],
                            'area1' => ['name' => '서울특별시', 'coords' => []],
                            'area2' => ['name' => '강남구', 'coords' => []],
                            'area3' => ['name' => '역삼동', 'coords' => []],
                            'area4' => ['name' => '', 'coords' => []],
                        ],
                        'land' => [
                            'type' => '1',
                            'number1' => '737',
                            'number2' => '',
                            'addition0' => [
                                'type' => 'roadaddr',
                                'value' => '테헤란로 152',
                            ],
                            'addition1' => [],
                            'addition2' => [],
                            'addition3' => [],
                            'addition4' => [],
                            'name' => '역삼동 737',
                            'coords' => [],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->reverseGeocode(37.4999940, 127.0357270);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('address', $result);
        $this->assertArrayHasKey('roadAddress', $result);
        $this->assertArrayHasKey('area', $result);
        $this->assertEquals('역삼동 737', $result['address']);
        $this->assertEquals('테헤란로 152', $result['roadAddress']);
        $this->assertIsArray($result['area']);
        $this->assertEquals('서울특별시', $result['area']['level1']);
        $this->assertEquals('강남구', $result['area']['level2']);
        $this->assertEquals('역삼동', $result['area']['level3']);

        // Verify request was made with correct params
        Http::assertSent(function ($request) {
            return $request->hasHeader('X-NCP-APIGW-API-KEY-ID') &&
                   str_contains($request->url(), 'map-reversegeocode/v2/gc');
        });
    }

    /** @test */
    public function it_returns_null_when_reverse_geocode_finds_no_results()
    {
        Http::fake([
            '*/map-reversegeocode/v2/gc*' => Http::response([
                'status' => [
                    'code' => 0,
                    'name' => 'ok',
                    'message' => 'done',
                ],
                'results' => [],
            ], 200),
        ]);

        $result = $this->service->reverseGeocode(0.0, 0.0);

        $this->assertNull($result);
    }

    /** @test */
    public function it_can_get_directions_5_route()
    {
        Http::fake([
            '*/map-direction/v1/driving*' => Http::response([
                'code' => 0,
                'message' => 'ok',
                'currentDateTime' => '2025-11-06T10:00:00.000+09:00',
                'route' => [
                    'traoptimal' => [
                        [
                            'summary' => [
                                'start' => ['location' => [127.0357270, 37.4999940]],
                                'goal' => ['location' => [127.1234567, 37.5678901]],
                                'distance' => 12345,
                                'duration' => 1234567,
                                'departureTime' => '2025-11-06T10:00:00.000+09:00',
                                'bbox' => [[127.0357270, 37.4999940], [127.1234567, 37.5678901]],
                                'tollFare' => 1000,
                                'taxiFare' => 15000,
                                'fuelPrice' => 2000,
                            ],
                            'path' => [[127.0357270, 37.4999940], [127.1234567, 37.5678901]],
                            'section' => [],
                            'guide' => [],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->getDirections5(
            37.4999940,
            127.0357270,
            37.5678901,
            127.1234567
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('distance', $result);
        $this->assertArrayHasKey('duration', $result);
        $this->assertArrayHasKey('path', $result);
        $this->assertArrayHasKey('tollFare', $result);
        $this->assertArrayHasKey('taxiFare', $result);
        $this->assertArrayHasKey('fuelPrice', $result);
        $this->assertEquals(12345, $result['distance']);
        $this->assertEquals(1234567, $result['duration']);
        $this->assertIsArray($result['path']);

        // Verify request
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'map-direction/v1/driving');
        });
    }

    /** @test */
    public function it_can_get_directions_15_route_with_waypoints()
    {
        Http::fake([
            '*/map-direction-15/v1/driving*' => Http::response([
                'code' => 0,
                'message' => 'ok',
                'currentDateTime' => '2025-11-06T10:00:00.000+09:00',
                'route' => [
                    'traoptimal' => [
                        [
                            'summary' => [
                                'start' => ['location' => [127.0357270, 37.4999940]],
                                'goal' => ['location' => [127.1234567, 37.5678901]],
                                'waypoints' => [
                                    ['location' => [127.0555555, 37.5123456]],
                                    ['location' => [127.0777777, 37.5345678]],
                                ],
                                'distance' => 15678,
                                'duration' => 1567890,
                                'departureTime' => '2025-11-06T10:00:00.000+09:00',
                                'bbox' => [[127.0357270, 37.4999940], [127.1234567, 37.5678901]],
                                'tollFare' => 2000,
                                'taxiFare' => 20000,
                                'fuelPrice' => 3000,
                            ],
                            'path' => [
                                [127.0357270, 37.4999940],
                                [127.0555555, 37.5123456],
                                [127.0777777, 37.5345678],
                                [127.1234567, 37.5678901],
                            ],
                            'section' => [],
                            'guide' => [],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->getDirections15(
            37.4999940,
            127.0357270,
            37.5678901,
            127.1234567,
            [
                ['lat' => 37.5123456, 'lng' => 127.0555555],
                ['lat' => 37.5345678, 'lng' => 127.0777777],
            ]
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('distance', $result);
        $this->assertArrayHasKey('duration', $result);
        $this->assertArrayHasKey('path', $result);
        $this->assertArrayHasKey('waypoints', $result);
        $this->assertEquals(15678, $result['distance']);
        $this->assertEquals(1567890, $result['duration']);
        $this->assertIsArray($result['path']);
        $this->assertCount(4, $result['path']); // start + 2 waypoints + goal
        $this->assertIsArray($result['waypoints']);

        // Verify request with waypoints
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'map-direction-15/v1/driving') &&
                   str_contains($request->url(), 'waypoints=');
        });
    }

    /** @test */
    public function it_returns_null_when_service_is_disabled()
    {
        config(['services.naver.maps.enabled' => false]);
        $service = new NaverMapsService();

        $this->assertNull($service->geocode('test'));
        $this->assertNull($service->reverseGeocode(37.5, 127.0));
        $this->assertNull($service->getDirections5(37.5, 127.0, 37.6, 127.1));
        $this->assertNull($service->getDirections15(37.5, 127.0, 37.6, 127.1));
    }

    /** @test */
    public function it_handles_api_errors_gracefully()
    {
        Http::fake([
            '*/map-geocode/v2/geocode*' => Http::response([
                'errorMessage' => 'Invalid query parameter',
                'errorCode' => 'ERR001',
            ], 400),
        ]);

        $this->expectException(\App\Exceptions\NaverApiException::class);
        $this->expectExceptionMessage('Invalid query parameter');

        $this->service->geocode('');
    }

    /** @test */
    public function it_can_get_directions_5_with_options()
    {
        Http::fake([
            '*/map-direction/v1/driving*' => Http::response([
                'code' => 0,
                'message' => 'ok',
                'route' => [
                    'traavoidtoll' => [
                        [
                            'summary' => [
                                'start' => ['location' => [127.0357270, 37.4999940]],
                                'goal' => ['location' => [127.1234567, 37.5678901]],
                                'distance' => 13000,
                                'duration' => 1300000,
                                'departureTime' => '2025-11-06T10:00:00.000+09:00',
                                'bbox' => [[127.0357270, 37.4999940], [127.1234567, 37.5678901]],
                                'tollFare' => 0, // No toll because avoided
                                'taxiFare' => 16000,
                                'fuelPrice' => 2200,
                            ],
                            'path' => [[127.0357270, 37.4999940], [127.1234567, 37.5678901]],
                            'section' => [],
                            'guide' => [],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->getDirections5(
            37.4999940,
            127.0357270,
            37.5678901,
            127.1234567,
            ['option' => 'traavoidtoll'] // Avoid toll roads
        );

        $this->assertIsArray($result);
        $this->assertEquals(0, $result['tollFare']); // Should be 0 for avoid toll option

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'option=traavoidtoll');
        });
    }

    /** @test */
    public function it_can_get_directions_15_without_waypoints()
    {
        Http::fake([
            '*/map-direction-15/v1/driving*' => Http::response([
                'code' => 0,
                'message' => 'ok',
                'route' => [
                    'traoptimal' => [
                        [
                            'summary' => [
                                'start' => ['location' => [127.0357270, 37.4999940]],
                                'goal' => ['location' => [127.1234567, 37.5678901]],
                                'distance' => 12345,
                                'duration' => 1234567,
                                'departureTime' => '2025-11-06T10:00:00.000+09:00',
                                'bbox' => [[127.0357270, 37.4999940], [127.1234567, 37.5678901]],
                                'tollFare' => 1000,
                                'taxiFare' => 15000,
                                'fuelPrice' => 2000,
                            ],
                            'path' => [[127.0357270, 37.4999940], [127.1234567, 37.5678901]],
                            'section' => [],
                            'guide' => [],
                        ],
                    ],
                ],
            ], 200),
        ]);

        // Call without waypoints parameter
        $result = $this->service->getDirections15(
            37.4999940,
            127.0357270,
            37.5678901,
            127.1234567
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('waypoints', $result);
        $this->assertEmpty($result['waypoints']); // Should be empty array
    }
}
