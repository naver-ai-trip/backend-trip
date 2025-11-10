<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Naver\SearchTrendService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SearchTrendControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->postJson('/api/search-trends/keywords', [
            'keywords' => ['제주도 여행'],
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
        ]);

        $response->assertUnauthorized();
    }

    /** @test */
    public function it_can_get_keyword_trends()
    {
        Http::fake([
            '*/datalab/v1/search' => Http::response([
                'startDate' => '2024-01-01',
                'endDate' => '2024-12-31',
                'timeUnit' => 'month',
                'results' => [
                    [
                        'title' => '제주도 여행',
                        'keywords' => ['제주도 여행'],
                        'data' => [
                            ['period' => '2024-01', 'ratio' => 75.5],
                            ['period' => '2024-02', 'ratio' => 80.2],
                            ['period' => '2024-03', 'ratio' => 85.0],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/search-trends/keywords', [
                'keywords' => ['제주도 여행'],
                'start_date' => '2024-01-01',
                'end_date' => '2024-12-31',
                'time_unit' => 'month',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'startDate' => '2024-01-01',
                    'endDate' => '2024-12-31',
                    'timeUnit' => 'month',
                ],
            ]);
    }

    /** @test */
    public function it_validates_keyword_trends_request()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/search-trends/keywords', [
                'keywords' => [],
                'start_date' => 'invalid-date',
                'end_date' => '2024-12-31',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['keywords', 'start_date']);
    }

    /** @test */
    public function it_can_get_keyword_trends_with_demographic_filters()
    {
        Http::fake([
            '*/datalab/v1/search' => Http::response([
                'startDate' => '2024-01-01',
                'endDate' => '2024-12-31',
                'timeUnit' => 'month',
                'results' => [
                    [
                        'title' => '제주도 여행',
                        'keywords' => ['제주도 여행'],
                        'data' => [
                            ['period' => '2024-01', 'ratio' => 75.5],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/search-trends/keywords', [
                'keywords' => ['제주도 여행'],
                'start_date' => '2024-01-01',
                'end_date' => '2024-12-31',
                'time_unit' => 'month',
                'device' => 'mo',
                'gender' => 'f',
                'ages' => ['3', '4', '5'],
            ]);

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function it_can_compare_multiple_keyword_groups()
    {
        Http::fake([
            '*/datalab/v1/search' => Http::response([
                'startDate' => '2024-01-01',
                'endDate' => '2024-12-31',
                'timeUnit' => 'month',
                'results' => [
                    [
                        'title' => '제주도 여행',
                        'keywords' => ['제주도 여행'],
                        'data' => [['period' => '2024-01', 'ratio' => 75.5]],
                    ],
                    [
                        'title' => '부산 여행',
                        'keywords' => ['부산 여행'],
                        'data' => [['period' => '2024-01', 'ratio' => 65.2]],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/search-trends/compare', [
                'keyword_groups' => [
                    ['제주도 여행'],
                    ['부산 여행'],
                    ['강릉 여행'],
                ],
                'start_date' => '2024-01-01',
                'end_date' => '2024-12-31',
                'time_unit' => 'month',
            ]);

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function it_validates_compare_keywords_request()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/search-trends/compare', [
                'keyword_groups' => [],
                'start_date' => '2024-01-01',
                'end_date' => '2024-12-31',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['keyword_groups']);
    }

    /** @test */
    public function it_limits_keyword_groups_to_five()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/search-trends/compare', [
                'keyword_groups' => [
                    ['keyword1'],
                    ['keyword2'],
                    ['keyword3'],
                    ['keyword4'],
                    ['keyword5'],
                    ['keyword6'], // 6th group (over limit)
                ],
                'start_date' => '2024-01-01',
                'end_date' => '2024-12-31',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['keyword_groups']);
    }

    /** @test */
    public function it_can_get_age_gender_trends()
    {
        Http::fake([
            '*/datalab/v1/search' => Http::response([
                'startDate' => '2024-01-01',
                'endDate' => '2024-12-31',
                'results' => [
                    [
                        'title' => '제주도 여행',
                        'data' => [
                            ['period' => '2024-01', 'group' => '20-24', 'ratio' => 45.5],
                            ['period' => '2024-01', 'group' => 'male', 'ratio' => 52.0],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/search-trends/demographics', [
                'keywords' => ['제주도 여행'],
                'start_date' => '2024-01-01',
                'end_date' => '2024-12-31',
            ]);

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function it_can_get_device_trends()
    {
        Http::fake([
            '*/datalab/v1/search' => Http::response([
                'startDate' => '2024-01-01',
                'endDate' => '2024-12-31',
                'results' => [
                    [
                        'title' => '제주도 여행',
                        'data' => [
                            ['period' => '2024-01', 'group' => 'mobile', 'ratio' => 75.5],
                            ['period' => '2024-01', 'group' => 'pc', 'ratio' => 24.5],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/search-trends/devices', [
                'keywords' => ['제주도 여행'],
                'start_date' => '2024-01-01',
                'end_date' => '2024-12-31',
            ]);

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function it_can_analyze_destination_popularity()
    {
        Http::fake([
            '*/datalab/v1/search' => Http::response([
                'startDate' => '2024-01-01',
                'endDate' => '2024-12-31',
                'timeUnit' => 'month',
                'results' => [
                    [
                        'title' => '제주도 여행',
                        'keywords' => ['제주도 여행'],
                        'data' => [
                            ['period' => '2024-01', 'ratio' => 65.5],
                            ['period' => '2024-08', 'ratio' => 95.5], // Peak
                            ['period' => '2024-12', 'ratio' => 70.0],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/search-trends/destination-popularity', [
                'destination' => '제주도 여행',
                'start_date' => '2024-01-01',
                'end_date' => '2024-12-31',
                'time_unit' => 'month',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'keyword' => '제주도 여행',
                    'peak_period' => '2024-08',
                    'peak_ratio' => 95.5,
                ],
            ]);
    }

    /** @test */
    public function it_can_get_seasonal_insights()
    {
        Http::fake([
            '*/datalab/v1/search' => Http::response([
                'startDate' => now()->subMonths(12)->format('Y-m-d'),
                'endDate' => now()->format('Y-m-d'),
                'timeUnit' => 'month',
                'results' => [
                    [
                        'title' => '제주도 여행',
                        'keywords' => ['제주도 여행'],
                        'data' => [
                            ['period' => '2024-01', 'ratio' => 75.0], // Winter
                            ['period' => '2024-07', 'ratio' => 95.0], // Summer peak
                            ['period' => '2024-12', 'ratio' => 80.0], // Winter peak
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/search-trends/seasonal-insights', [
                'keyword' => '제주도 여행',
                'months' => 12,
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'keyword' => '제주도 여행',
                ],
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'keyword',
                    'analysis_period',
                    'summer_peak',
                    'winter_peak',
                ],
            ]);
    }

    /** @test */
    public function it_validates_seasonal_insights_request()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/search-trends/seasonal-insights', [
                'keyword' => '',
                'months' => 50, // Over max
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['keyword', 'months']);
    }

    /** @test */
    public function it_returns_503_when_service_is_disabled()
    {
        // Mock disabled service
        $this->mock(SearchTrendService::class, function ($mock) {
            $mock->shouldReceive('getKeywordTrends')->andReturn(null);
        });

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/search-trends/keywords', [
                'keywords' => ['제주도 여행'],
                'start_date' => '2024-01-01',
                'end_date' => '2024-12-31',
            ]);

        $response->assertStatus(503)
            ->assertJson([
                'success' => false,
                'message' => 'Search Trend API is disabled',
            ]);
    }

    /** @test */
    public function it_handles_api_errors_gracefully()
    {
        Http::fake([
            '*/datalab/v1/search' => Http::response(['error' => 'API Error'], 500),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/search-trends/keywords', [
                'keywords' => ['제주도 여행'],
                'start_date' => '2024-01-01',
                'end_date' => '2024-12-31',
            ]);

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
            ])
            ->assertJsonStructure([
                'success',
                'message',
            ]);
    }

    /** @test */
    public function it_validates_date_format()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/search-trends/keywords', [
                'keywords' => ['제주도 여행'],
                'start_date' => '01/01/2024', // Wrong format
                'end_date' => '12-31-2024', // Wrong format
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_date', 'end_date']);
    }

    /** @test */
    public function it_validates_end_date_after_start_date()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/search-trends/keywords', [
                'keywords' => ['제주도 여행'],
                'start_date' => '2024-12-31',
                'end_date' => '2024-01-01', // Before start_date
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
    }

    /** @test */
    public function it_validates_time_unit_enum()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/search-trends/keywords', [
                'keywords' => ['제주도 여행'],
                'start_date' => '2024-01-01',
                'end_date' => '2024-12-31',
                'time_unit' => 'year', // Invalid
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['time_unit']);
    }

    /** @test */
    public function it_validates_device_enum()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/search-trends/keywords', [
                'keywords' => ['제주도 여행'],
                'start_date' => '2024-01-01',
                'end_date' => '2024-12-31',
                'device' => 'tablet', // Invalid
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['device']);
    }

    /** @test */
    public function it_validates_gender_enum()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/search-trends/keywords', [
                'keywords' => ['제주도 여행'],
                'start_date' => '2024-01-01',
                'end_date' => '2024-12-31',
                'gender' => 'other', // Invalid
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['gender']);
    }

    /** @test */
    public function it_validates_age_groups()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/search-trends/keywords', [
                'keywords' => ['제주도 여행'],
                'start_date' => '2024-01-01',
                'end_date' => '2024-12-31',
                'ages' => ['12', '99'], // Invalid age groups
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ages.0', 'ages.1']);
    }

    /** @test */
    public function it_validates_maximum_keywords()
    {
        $keywords = array_fill(0, 21, '제주도'); // 21 keywords (over limit)

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/search-trends/keywords', [
                'keywords' => $keywords,
                'start_date' => '2024-01-01',
                'end_date' => '2024-12-31',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['keywords']);
    }
}
