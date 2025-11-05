<?php

namespace Tests\Feature;

use App\Jobs\ProcessImageModeration;
use App\Models\CheckpointImage;
use App\Models\Comment;
use App\Models\Review;
use App\Models\User;
use App\Models\Place;
use App\Services\Naver\GreenEyeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class GreenEyeServiceTest extends TestCase
{
    use RefreshDatabase;

    protected GreenEyeService $greenEyeService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->greenEyeService = app(GreenEyeService::class);
    }

    /**
     * Test image URLs from Unsplash
     */
    protected function getTestImageUrls(): array
    {
        return [
            'https://images.unsplash.com/photo-1506748686214-e9df14d4d9d0?w=800',  // Landscape
            'https://images.unsplash.com/photo-1472214103451-9374bd1c798e?w=800',  // Nature
        ];
    }

    /** @test */
    public function it_can_check_image_safety_with_first_url()
    {
        $imageUrls = $this->getTestImageUrls();
        
        // Call the service method directly
        $result = $this->greenEyeService->checkImageSafety($imageUrls[0]);

        // Assertions based on actual API response structure
        $this->assertIsArray($result);
        $this->assertArrayHasKey('safe', $result);
        $this->assertArrayHasKey('reason', $result);
        $this->assertArrayHasKey('adult', $result);
        $this->assertArrayHasKey('porn', $result);
        $this->assertArrayHasKey('sexy', $result);
        $this->assertArrayHasKey('normal', $result);
        
        // Verify the scores are numeric
        $this->assertIsNumeric($result['adult']);
        $this->assertIsNumeric($result['porn']);
        $this->assertIsNumeric($result['sexy']);
        $this->assertIsNumeric($result['normal']);
        
        // Verify safe is boolean
        $this->assertIsBool($result['safe']);
        
        // Log the result for debugging
        dump('First Image Result:', $result);
    }

    /** @test */
    public function it_can_check_image_safety_with_second_url()
    {
        $imageUrls = $this->getTestImageUrls();
        
        // Call the service method directly
        $result = $this->greenEyeService->checkImageSafety($imageUrls[1]);

        // Assertions based on actual API response structure
        $this->assertIsArray($result);
        $this->assertArrayHasKey('safe', $result);
        $this->assertArrayHasKey('reason', $result);
        $this->assertArrayHasKey('adult', $result);
        $this->assertArrayHasKey('porn', $result);
        $this->assertArrayHasKey('sexy', $result);
        $this->assertArrayHasKey('normal', $result);
        
        // Verify the scores are numeric
        $this->assertIsNumeric($result['adult']);
        $this->assertIsNumeric($result['porn']);
        $this->assertIsNumeric($result['sexy']);
        $this->assertIsNumeric($result['normal']);
        
        // Verify safe is boolean
        $this->assertIsBool($result['safe']);
        
        // Log the result for debugging
        dump('Second Image Result:', $result);
    }

    /** @test */
    public function it_compares_scores_correctly()
    {
        $imageUrls = $this->getTestImageUrls();
        
        // Call the service method
        $result = $this->greenEyeService->checkImageSafety($imageUrls[0]);

        // According to API docs, content is safe when normal > max(adult, porn, sexy)
        $maxInappropriate = max($result['adult'], $result['porn'], $result['sexy']);
        $expectedSafe = $result['normal'] > $maxInappropriate;

        $this->assertEquals($expectedSafe, $result['safe'],
            "Safe flag should be true when normal ({$result['normal']}) > max inappropriate ({$maxInappropriate})");
    }

    /** @test */
    public function it_returns_proper_structure_when_service_disabled()
    {
        // Temporarily disable the service
        config(['services.naver.greeneye.enabled' => false]);
        
        // Re-instantiate the service to pick up the new config
        $disabledService = new GreenEyeService();
        
        $imageUrls = $this->getTestImageUrls();
        $result = $disabledService->checkImageSafety($imageUrls[0]);

        // Should return safe with disabled message
        $this->assertTrue($result['safe']);
        $this->assertEquals('Content moderation disabled', $result['reason']);
        $this->assertNull($result['adult']);
        $this->assertNull($result['porn']);
        $this->assertNull($result['sexy']);
        $this->assertNull($result['normal']);
    }

    /** @test */
    public function it_dispatches_moderation_job_to_queue()
    {
        Queue::fake();

        $imageUrls = $this->getTestImageUrls();

        // Dispatch the job
        ProcessImageModeration::dispatch('review', 1, $imageUrls[0]);

        // Assert the job was pushed to the queue
        Queue::assertPushed(ProcessImageModeration::class, function ($job) use ($imageUrls) {
            return $job->modelType === 'review' &&
                   $job->modelId === 1 &&
                   $job->imagePath === $imageUrls[0];
        });
    }

    /** @test */
    public function it_processes_review_image_moderation_via_queue()
    {
        // Create a user and place for the review
        $user = User::factory()->create();
        $place = Place::factory()->create();

        // Create a review with an external image URL (without moderation fields)
        $imageUrls = $this->getTestImageUrls();
        $review = Review::factory()->create([
            'user_id' => $user->id,
            'reviewable_type' => Place::class,
            'reviewable_id' => $place->id,
            'images' => [$imageUrls[0]],
        ]);

        // Dispatch and execute the job
        $job = new ProcessImageModeration('review', $review->id, $imageUrls[0]);
        $job->handle($this->greenEyeService);

        // Refresh the model
        $review->refresh();

        // Assert moderation results were stored (note: plural 'moderation_results')
        $this->assertNotNull($review->moderation_results);
        $this->assertIsArray($review->moderation_results);
        $this->assertArrayHasKey('safe', $review->moderation_results);
        $this->assertArrayHasKey('adult', $review->moderation_results);
        $this->assertArrayHasKey('porn', $review->moderation_results);
        $this->assertArrayHasKey('sexy', $review->moderation_results);
        $this->assertArrayHasKey('normal', $review->moderation_results);

        // Assert the image is not flagged (safe content)
        $this->assertFalse($review->is_flagged);
        $this->assertTrue($review->moderation_results['safe']);

        dump('Review Moderation Result:', $review->moderation_results);
    }

    /** @test */
    public function it_processes_comment_image_moderation_via_queue()
    {
        // Create a user and trip for the comment
        $user = User::factory()->create();
        $trip = \App\Models\Trip::factory()->create(['user_id' => $user->id]);

        // Create a comment with an external image URL (without moderation fields)
        $imageUrls = $this->getTestImageUrls();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'entity_type' => \App\Models\Trip::class,
            'entity_id' => $trip->id,
            'images' => [$imageUrls[1]],
        ]);

        // Dispatch and execute the job
        $job = new ProcessImageModeration('comment', $comment->id, $imageUrls[1]);
        $job->handle($this->greenEyeService);

        // Refresh the model
        $comment->refresh();

        // Assert moderation results were stored (note: plural 'moderation_results' for Comment)
        $this->assertNotNull($comment->moderation_results);
        $this->assertIsArray($comment->moderation_results);
        $this->assertArrayHasKey('safe', $comment->moderation_results);
        $this->assertArrayHasKey('adult', $comment->moderation_results);
        $this->assertArrayHasKey('porn', $comment->moderation_results);
        $this->assertArrayHasKey('sexy', $comment->moderation_results);
        $this->assertArrayHasKey('normal', $comment->moderation_results);

        // Assert the image is not flagged (safe content)
        $this->assertFalse($comment->is_flagged);
        $this->assertTrue($comment->moderation_results['safe']);

        dump('Comment Moderation Result:', $comment->moderation_results);
    }

    /** @test */
    public function it_processes_checkpoint_image_moderation_via_queue()
    {
        // Create a user, trip, and checkpoint for the image
        $user = User::factory()->create();
        $trip = \App\Models\Trip::factory()->create(['user_id' => $user->id]);
        $checkpoint = \App\Models\MapCheckpoint::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $user->id,
        ]);

        // Create a checkpoint image with an external URL (without moderation fields)
        $imageUrls = $this->getTestImageUrls();
        $checkpointImage = CheckpointImage::factory()->create([
            'map_checkpoint_id' => $checkpoint->id,
            'user_id' => $user->id,
            'file_path' => $imageUrls[0], // Use file_path, not path
        ]);

        // Dispatch and execute the job
        $job = new ProcessImageModeration('checkpoint_image', $checkpointImage->id, $imageUrls[0]);
        $job->handle($this->greenEyeService);

        // Refresh the model
        $checkpointImage->refresh();

        // Assert moderation results were stored (note: plural 'moderation_results')
        $this->assertNotNull($checkpointImage->moderation_results);
        $this->assertIsArray($checkpointImage->moderation_results);
        $this->assertArrayHasKey('safe', $checkpointImage->moderation_results);
        $this->assertArrayHasKey('adult', $checkpointImage->moderation_results);
        $this->assertArrayHasKey('porn', $checkpointImage->moderation_results);
        $this->assertArrayHasKey('sexy', $checkpointImage->moderation_results);
        $this->assertArrayHasKey('normal', $checkpointImage->moderation_results);

        // Assert the image is not flagged (safe content)
        $this->assertFalse($checkpointImage->is_flagged);
        $this->assertTrue($checkpointImage->moderation_results['safe']);

        dump('CheckpointImage Moderation Result:', $checkpointImage->moderation_results);
    }

    /** @test */
    public function it_skips_moderation_when_service_disabled()
    {
        // Temporarily disable the service
        config(['services.naver.greeneye.enabled' => false]);

        // Create a review (without moderation fields)
        $user = User::factory()->create();
        $place = Place::factory()->create();
        $imageUrls = $this->getTestImageUrls();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'reviewable_type' => Place::class,
            'reviewable_id' => $place->id,
            'images' => [$imageUrls[0]],
        ]);

        // Re-instantiate service with disabled config
        $disabledService = new GreenEyeService();

        // Dispatch and execute the job
        $job = new ProcessImageModeration('review', $review->id, $imageUrls[0]);
        $job->handle($disabledService);

        // Refresh the model
        $review->refresh();

        // Assert moderation was skipped (no results stored)
        $this->assertNull($review->moderation_results);
        $this->assertFalse($review->is_flagged);
    }

    /** @test */
    public function it_handles_job_retries_on_failure()
    {
        // Create a review (without moderation fields)
        $user = User::factory()->create();
        $place = Place::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'reviewable_type' => Place::class,
            'reviewable_id' => $place->id,
            'images' => ['https://invalid-url-that-will-fail.com/image.jpg'],
        ]);

        // Create job instance
        $job = new ProcessImageModeration('review', $review->id, 'https://invalid-url-that-will-fail.com/image.jpg');

        // Assert job has retry configuration
        $this->assertEquals(3, $job->tries);
        $this->assertEquals(5, $job->backoff);
    }
}


