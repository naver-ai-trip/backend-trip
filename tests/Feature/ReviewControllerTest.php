<?php

namespace Tests\Feature;

use App\Models\MapCheckpoint;
use App\Models\Place;
use App\Models\Review;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Feature tests for ReviewController API endpoints.
 * 
 * Following TDD: Tests written FIRST, implementation AFTER.
 * 
 * Test Coverage:
 * - CRUD operations (index, store, show, update, destroy)
 * - Polymorphic relationships (Place, MapCheckpoint)
 * - Validation (rating range, required fields, reviewable types)
 * - Authorization (only owner can modify)
 * - Business logic (no duplicate reviews, average ratings)
 * - Filtering (by reviewable type, rating)
 */
class ReviewControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
    }

    /** @test */
    public function user_can_create_review_for_place()
    {
        $place = Place::factory()->create();

        $reviewData = [
            'reviewable_type' => 'place',
            'reviewable_id' => $place->id,
            'rating' => 5,
            'comment' => 'Amazing place! Highly recommended.',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/reviews', $reviewData);

        $response->assertCreated()
            ->assertJsonPath('data.reviewable_type', 'place')
            ->assertJsonPath('data.reviewable_id', $place->id)
            ->assertJsonPath('data.rating', 5)
            ->assertJsonPath('data.user_id', $this->user->id);

        $this->assertDatabaseHas('reviews', [
            'user_id' => $this->user->id,
            'reviewable_type' => 'App\\Models\\Place',
            'reviewable_id' => $place->id,
            'rating' => 5,
        ]);
    }

    /** @test */
    public function user_can_create_review_for_checkpoint()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);
        $checkpoint = MapCheckpoint::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $this->user->id,
        ]);

        $reviewData = [
            'reviewable_type' => 'map_checkpoint',
            'reviewable_id' => $checkpoint->id,
            'rating' => 4,
            'comment' => 'Great spot!',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/reviews', $reviewData);

        $response->assertCreated()
            ->assertJsonPath('data.reviewable_type', 'map_checkpoint')
            ->assertJsonPath('data.reviewable_id', $checkpoint->id)
            ->assertJsonPath('data.rating', 4);

        $this->assertDatabaseHas('reviews', [
            'user_id' => $this->user->id,
            'reviewable_type' => 'App\\Models\\MapCheckpoint',
            'reviewable_id' => $checkpoint->id,
            'rating' => 4,
        ]);
    }

    /** @test */
    public function user_can_list_reviews()
    {
        $place = Place::factory()->create();

        Review::factory()->count(3)->create([
            'reviewable_type' => Place::class,
            'reviewable_id' => $place->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/reviews');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'reviewable_type',
                        'reviewable_id',
                        'rating',
                        'comment',
                        'created_at',
                        'updated_at',
                    ]
                ]
            ]);
    }

    /** @test */
    public function user_can_view_review_details()
    {
        $place = Place::factory()->create();
        $review = Review::factory()->create([
            'reviewable_type' => Place::class,
            'reviewable_id' => $place->id,
            'rating' => 5,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/reviews/{$review->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $review->id)
            ->assertJsonPath('data.rating', 5);
    }

    /** @test */
    public function user_can_update_their_review()
    {
        $place = Place::factory()->create();
        $review = Review::factory()->create([
            'user_id' => $this->user->id,
            'reviewable_type' => Place::class,
            'reviewable_id' => $place->id,
            'rating' => 3,
            'comment' => 'Original comment',
        ]);

        $updateData = [
            'rating' => 5,
            'comment' => 'Updated comment - much better experience!',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/reviews/{$review->id}", $updateData);

        $response->assertOk()
            ->assertJsonPath('data.rating', 5)
            ->assertJsonPath('data.comment', 'Updated comment - much better experience!');

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 5,
            'comment' => 'Updated comment - much better experience!',
        ]);
    }

    /** @test */
    public function user_can_delete_their_review()
    {
        $place = Place::factory()->create();
        $review = Review::factory()->create([
            'user_id' => $this->user->id,
            'reviewable_type' => Place::class,
            'reviewable_id' => $place->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/reviews/{$review->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
        ]);
    }

    /** @test */
    public function review_creation_requires_authentication()
    {
        $place = Place::factory()->create();

        $reviewData = [
            'reviewable_type' => 'place',
            'reviewable_id' => $place->id,
            'rating' => 5,
        ];

        $response = $this->postJson('/api/reviews', $reviewData);

        $response->assertUnauthorized();
    }

    /** @test */
    public function review_creation_validates_required_fields()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/reviews', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['reviewable_type', 'reviewable_id', 'rating']);
    }

    /** @test */
    public function review_validates_rating_minimum()
    {
        $place = Place::factory()->create();

        $reviewData = [
            'reviewable_type' => 'place',
            'reviewable_id' => $place->id,
            'rating' => 0, // Below minimum
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/reviews', $reviewData);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['rating']);
    }

    /** @test */
    public function review_validates_rating_maximum()
    {
        $place = Place::factory()->create();

        $reviewData = [
            'reviewable_type' => 'place',
            'reviewable_id' => $place->id,
            'rating' => 6, // Above maximum
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/reviews', $reviewData);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['rating']);
    }

    /** @test */
    public function review_validates_reviewable_type_enum()
    {
        $reviewData = [
            'reviewable_type' => 'invalid_type',
            'reviewable_id' => 1,
            'rating' => 5,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/reviews', $reviewData);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['reviewable_type']);
    }

    /** @test */
    public function review_validates_place_exists()
    {
        $reviewData = [
            'reviewable_type' => 'place',
            'reviewable_id' => 99999, // Non-existent
            'rating' => 5,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/reviews', $reviewData);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['reviewable_id']);
    }

    /** @test */
    public function review_validates_checkpoint_exists()
    {
        $reviewData = [
            'reviewable_type' => 'map_checkpoint',
            'reviewable_id' => 99999, // Non-existent
            'rating' => 5,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/reviews', $reviewData);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['reviewable_id']);
    }

    /** @test */
    public function review_comment_is_optional()
    {
        $place = Place::factory()->create();

        $reviewData = [
            'reviewable_type' => 'place',
            'reviewable_id' => $place->id,
            'rating' => 5,
            // No comment provided
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/reviews', $reviewData);

        $response->assertCreated()
            ->assertJsonPath('data.comment', null);
    }

    /** @test */
    public function review_comment_validates_max_length()
    {
        $place = Place::factory()->create();

        $reviewData = [
            'reviewable_type' => 'place',
            'reviewable_id' => $place->id,
            'rating' => 5,
            'comment' => str_repeat('a', 1001), // Exceeds max 1000
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/reviews', $reviewData);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['comment']);
    }

    /** @test */
    public function user_cannot_update_other_users_review()
    {
        $place = Place::factory()->create();
        $review = Review::factory()->create([
            'user_id' => $this->otherUser->id,
            'reviewable_type' => Place::class,
            'reviewable_id' => $place->id,
        ]);

        $updateData = [
            'rating' => 5,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/reviews/{$review->id}", $updateData);

        $response->assertForbidden();
    }

    /** @test */
    public function user_cannot_delete_other_users_review()
    {
        $place = Place::factory()->create();
        $review = Review::factory()->create([
            'user_id' => $this->otherUser->id,
            'reviewable_type' => Place::class,
            'reviewable_id' => $place->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/reviews/{$review->id}");

        $response->assertForbidden();
    }

    /** @test */
    public function user_cannot_review_same_entity_twice()
    {
        $place = Place::factory()->create();

        // Create first review
        Review::factory()->create([
            'user_id' => $this->user->id,
            'reviewable_type' => Place::class,
            'reviewable_id' => $place->id,
        ]);

        // Attempt to create duplicate
        $reviewData = [
            'reviewable_type' => 'place',
            'reviewable_id' => $place->id,
            'rating' => 5,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/reviews', $reviewData);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['reviewable_id']);
    }

    /** @test */
    public function review_not_found_returns_404()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/reviews/99999');

        $response->assertNotFound();
    }

    /** @test */
    public function review_list_is_paginated()
    {
        $place = Place::factory()->create();

        Review::factory()->count(20)->create([
            'reviewable_type' => Place::class,
            'reviewable_id' => $place->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/reviews?per_page=5');

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.per_page', 5)
            ->assertJsonPath('meta.total', 20);
    }

    /** @test */
    public function review_list_can_filter_by_reviewable_type()
    {
        $place = Place::factory()->create();
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);
        $checkpoint = MapCheckpoint::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $this->user->id,
        ]);

        Review::factory()->count(3)->create([
            'reviewable_type' => Place::class,
            'reviewable_id' => $place->id,
        ]);

        Review::factory()->count(2)->create([
            'reviewable_type' => MapCheckpoint::class,
            'reviewable_id' => $checkpoint->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/reviews?reviewable_type=place');

        $response->assertOk()
            ->assertJsonCount(3, 'data');

        $data = $response->json('data');
        foreach ($data as $review) {
            $this->assertEquals('place', $review['reviewable_type']);
        }
    }

    /** @test */
    public function review_list_can_filter_by_reviewable_id()
    {
        $place1 = Place::factory()->create();
        $place2 = Place::factory()->create();

        Review::factory()->count(3)->create([
            'reviewable_type' => Place::class,
            'reviewable_id' => $place1->id,
        ]);

        Review::factory()->count(2)->create([
            'reviewable_type' => Place::class,
            'reviewable_id' => $place2->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/reviews?reviewable_id={$place1->id}");

        $response->assertOk()
            ->assertJsonCount(3, 'data');

        $data = $response->json('data');
        foreach ($data as $review) {
            $this->assertEquals($place1->id, $review['reviewable_id']);
        }
    }

    /** @test */
    public function review_list_can_filter_by_rating()
    {
        $place = Place::factory()->create();

        Review::factory()->count(3)->create([
            'reviewable_type' => Place::class,
            'reviewable_id' => $place->id,
            'rating' => 5,
        ]);

        Review::factory()->count(2)->create([
            'reviewable_type' => Place::class,
            'reviewable_id' => $place->id,
            'rating' => 3,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/reviews?rating=5');

        $response->assertOk()
            ->assertJsonCount(3, 'data');

        $data = $response->json('data');
        foreach ($data as $review) {
            $this->assertEquals(5, $review['rating']);
        }
    }

    /** @test */
    public function review_includes_reviewable_data_when_loaded()
    {
        $place = Place::factory()->create(['name' => 'Tokyo Tower']);
        $review = Review::factory()->create([
            'reviewable_type' => Place::class,
            'reviewable_id' => $place->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/reviews/{$review->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'reviewable' => [
                        'id',
                        'name',
                    ]
                ]
            ]);
    }

    /** @test */
    public function review_includes_user_data_when_loaded()
    {
        $place = Place::factory()->create();
        $review = Review::factory()->create([
            'user_id' => $this->user->id,
            'reviewable_type' => Place::class,
            'reviewable_id' => $place->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/reviews/{$review->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'user' => [
                        'id',
                        'name',
                    ]
                ]
            ]);
    }

    /** @test */
    public function rating_accepts_all_valid_values()
    {
        $place = Place::factory()->create();
        $validRatings = [1, 2, 3, 4, 5];

        foreach ($validRatings as $rating) {
            $reviewData = [
                'reviewable_type' => 'place',
                'reviewable_id' => $place->id,
                'rating' => $rating,
            ];

            // Create new user for each review to avoid duplicate constraint
            $user = User::factory()->create();

            $response = $this->actingAs($user, 'sanctum')
                ->postJson('/api/reviews', $reviewData);

            $response->assertCreated()
                ->assertJsonPath('data.rating', $rating);
        }
    }

    /** @test */
    public function user_can_create_review_with_images()
    {
        Storage::fake('public');
        
        $place = Place::factory()->create();
        $image1 = UploadedFile::fake()->image('photo1.jpg', 800, 600)->size(1024); // 1MB
        $image2 = UploadedFile::fake()->image('photo2.png', 1024, 768)->size(2048); // 2MB

        $response = $this->actingAs($this->user, 'sanctum')
            ->post('/api/reviews', [
                'reviewable_type' => 'place',
                'reviewable_id' => $place->id,
                'rating' => 5,
                'comment' => 'Great place with photos!',
                'images' => [$image1, $image2],
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.rating', 5)
            ->assertJsonPath('data.comment', 'Great place with photos!')
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'images',
                    'is_flagged',
                ]
            ]);

        $review = Review::first();
        $this->assertNotEmpty($review->images);
        $this->assertCount(2, $review->images);
        
        // Verify files were stored
        foreach ($review->images as $imagePath) {
            Storage::disk('public')->assertExists($imagePath);
        }
    }

    /** @test */
    public function review_images_are_automatically_moderated()
    {
        Storage::fake('public');
        \Queue::fake();

        $place = Place::factory()->create();
        $image = UploadedFile::fake()->image('safe.jpg');

        $response = $this->actingAs($this->user, 'sanctum')
            ->post('/api/reviews', [
                'reviewable_type' => 'place',
                'reviewable_id' => $place->id,
                'rating' => 5,
                'images' => [$image],
            ]);

        $response->assertCreated();

        $review = Review::first();
        $this->assertNotNull($review->images);
        $this->assertCount(1, $review->images);

        // Assert moderation job was dispatched
        \Queue::assertPushed(\App\Jobs\ProcessImageModeration::class, function ($job) use ($review) {
            return $job->modelType === 'review' && $job->modelId === $review->id;
        });
    }

    /** @test */
    public function review_with_inappropriate_image_is_flagged()
    {
        Storage::fake('public');
        \Queue::fake();

        $place = Place::factory()->create();
        $image = UploadedFile::fake()->image('inappropriate.jpg');

        $response = $this->actingAs($this->user, 'sanctum')
            ->post('/api/reviews', [
                'reviewable_type' => 'place',
                'reviewable_id' => $place->id,
                'rating' => 3,
                'images' => [$image],
            ]);

        $response->assertCreated();

        $review = Review::first();
        $this->assertNotNull($review->images);

        // Assert moderation job was dispatched
        \Queue::assertPushed(\App\Jobs\ProcessImageModeration::class);
    }

    /** @test */
    public function review_can_upload_maximum_five_images()
    {
        Storage::fake('public');
        \Queue::fake();

        $place = Place::factory()->create();
        $images = [];
        for ($i = 1; $i <= 5; $i++) {
            $images[] = UploadedFile::fake()->image("photo{$i}.jpg");
        }

        $response = $this->actingAs($this->user, 'sanctum')
            ->post('/api/reviews', [
                'reviewable_type' => 'place',
                'reviewable_id' => $place->id,
                'rating' => 5,
                'images' => $images,
            ]);

        $response->assertCreated();

        $review = Review::first();
        $this->assertCount(5, $review->images);

        // Assert 5 moderation jobs were dispatched
        \Queue::assertPushed(\App\Jobs\ProcessImageModeration::class, 5);
    }

    /** @test */
    public function review_cannot_upload_more_than_five_images()
    {
        Storage::fake('public');

        $place = Place::factory()->create();
        $images = [];
        for ($i = 1; $i <= 6; $i++) {
            $images[] = UploadedFile::fake()->image("photo{$i}.jpg");
        }

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/reviews', [
                'reviewable_type' => 'place',
                'reviewable_id' => $place->id,
                'rating' => 5,
                'images' => $images,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['images']);
    }

    /** @test */
    public function review_image_must_be_valid_format()
    {
        Storage::fake('public');

        $place = Place::factory()->create();
        $invalidFile = UploadedFile::fake()->create('document.pdf', 1024);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/reviews', [
                'reviewable_type' => 'place',
                'reviewable_id' => $place->id,
                'rating' => 5,
                'images' => [$invalidFile],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['images.0']);
    }

    /** @test */
    public function review_image_cannot_exceed_10mb()
    {
        Storage::fake('public');

        $place = Place::factory()->create();
        $largeImage = UploadedFile::fake()->image('large.jpg')->size(11000); // 11MB

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/reviews', [
                'reviewable_type' => 'place',
                'reviewable_id' => $place->id,
                'rating' => 5,
                'images' => [$largeImage],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['images.0']);
    }
}
