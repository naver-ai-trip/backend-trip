<?php<?php



namespace Tests\Feature;namespace Tests\Feature;



use App\Jobs\ProcessImageModeration;use App\Jobs\ProcessImageModeration;

use App\Models\Review;use App\Models\Review;

use App\Models\Comment;use App\Models\Comment;

use App\Models\CheckpointImage;use App\Models\CheckpointImage;

use App\Models\Place;use App\Models\Place;

use App\Models\Trip;use App\Models\Trip;

use App\Models\User;use App\Models\User;

use App\Services\Naver\GreenEyeService;use App\Services\Naver\GreenEyeService;

use Illuminate\Foundation\Testing\RefreshDatabase;use Illuminate\Foundation\Testing\RefreshDatabase;

use Illuminate\Support\Facades\Storage;use Illuminate\Http\UploadedFile;

use Tests\TestCase;use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Storage;

class ProcessImageModerationJobTest extends TestCaseuse Tests\TestCase;

{

    use RefreshDatabase;class ProcessImageModerationJobTest extends TestCase

{

    protected User $user;    use RefreshDatabase;

    protected GreenEyeService $greenEyeService;

    protected User $user;

    protected function setUp(): void

    {    protected function setUp(): void

        parent::setUp();    {

        parent::setUp();

        // Use database queue driver for these tests (not sync)

        config(['queue.default' => 'database']);        // Use database queue driver for these tests (not sync)

        config(['queue.default' => 'database']);

        $this->user = User::factory()->create();

        $this->greenEyeService = app(GreenEyeService::class);        $this->user = User::factory()->create();

        Storage::fake('public');        Storage::fake('public');

    }    }



    /**    /**

     * Get public test image URLs.     * Get public test image URL for testing.

     * Using publicly accessible images from Unsplash.     * Using publicly accessible images from reliable sources.

     */     */

    protected function getTestImageUrl(int $index = 0): string    protected function getTestImageUrl(int $index = 0): string

    {    {

        $testImages = [        // Use publicly accessible images from Unsplash or other reliable sources

            'https://images.unsplash.com/photo-1506748686214-e9df14d4d9d0?w=800',  // Landscape        // These are free-to-use, publicly accessible images

            'https://images.unsplash.com/photo-1472214103451-9374bd1c798e?w=800',  // Nature        $testImages = [

        ];            'https://images.unsplash.com/photo-1506748686214-e9df14d4d9d0?w=800',  // Landscape

                    'https://images.unsplash.com/photo-1472214103451-9374bd1c798e?w=800',  // Nature

        return $testImages[$index % 2];        ];

    }        

        return $testImages[$index % 2];

    /** @test */    }

    public function job_processes_review_image_moderation()

    {    /** @test */

        // Create a review with an external image URL    public function job_processes_review_image_moderation()

        $place = Place::factory()->create();    {

        $review = Review::factory()->create([        // Create a review with an external image URL

            'user_id' => $this->user->id,        $place = Place::factory()->create();

            'reviewable_type' => Place::class,        $review = Review::factory()->create([

            'reviewable_id' => $place->id,            'user_id' => $this->user->id,

            'rating' => 5,            'reviewable_type' => Place::class,

        ]);            'reviewable_id' => $place->id,

            'rating' => 5,

        // Use real external URL for testing        ]);

        $imageUrl = $this->getTestImageUrl(0);

        $review->update(['images' => [$imageUrl]]);        // Use real external URL for testing

        $imageUrl = $this->getTestImageUrl(0);

        // Dispatch the job to queue        $review->update(['images' => [$imageUrl]]);

        ProcessImageModeration::dispatch('review', $review->id, $imageUrl);

        // Dispatch the job to queue

        // Process the queue        ProcessImageModeration::dispatch('review', $review->id, $imageUrl);

        $this->artisan('queue:work', [

            '--stop-when-empty' => true,        // Process the queue

            '--tries' => 1,        $this->artisan('queue:work', [

        ]);            '--stop-when-empty' => true,

            '--tries' => 1,

        // Refresh review from database        ]);

        $review->refresh();

        // Debug: Check database directly before refresh

        // Assertions based on actual Green-Eye API response format        $dbResult = DB::table('reviews')

        // Response includes: adult, normal, porn, sexy (NOT violence)            ->where('id', $review->id)

        $this->assertNotNull($review->moderation_result);            ->first();

        $this->assertIsArray($review->moderation_result);        

        $this->assertArrayHasKey('safe', $review->moderation_result);        dump('DB Result:', [

        $this->assertArrayHasKey('reason', $review->moderation_result);            'moderation_result' => $dbResult->moderation_result,

        $this->assertArrayHasKey('adult', $review->moderation_result);            'is_flagged' => $dbResult->is_flagged,

        $this->assertArrayHasKey('normal', $review->moderation_result);        ]);

        $this->assertArrayHasKey('porn', $review->moderation_result);

        $this->assertArrayHasKey('sexy', $review->moderation_result);        // Refresh review from database

                $review->refresh();

        // Verify is_flagged is set        

        $this->assertIsBool($review->is_flagged);        dump('Model Result after refresh:', [

    }            'moderation_result' => $review->moderation_result,

            'is_flagged' => $review->is_flagged,

    /** @test */        ]);

    public function job_processes_comment_image_moderation()

    {        // Assertions

        $trip = Trip::factory()->create(['user_id' => $this->user->id]);        $this->assertNotNull($review->moderation_result);

        $comment = Comment::factory()->create([        $this->assertIsArray($review->moderation_result);

            'user_id' => $this->user->id,        $this->assertArrayHasKey('safe', $review->moderation_result);

            'entity_type' => Trip::class,        $this->assertArrayHasKey('adult', $review->moderation_result);

            'entity_id' => $trip->id,        $this->assertArrayHasKey('porn', $review->moderation_result);

            'content' => 'Test comment',        $this->assertArrayHasKey('sexy', $review->moderation_result);

        ]);        $this->assertArrayHasKey('normal', $review->moderation_result);

    }

        // Use real external URL for testing

        $imageUrl = $this->getTestImageUrl(1);    /** @test */

        $comment->update(['images' => [$imageUrl]]);    public function job_processes_comment_image_moderation()

    {

        // Dispatch the job to queue        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        ProcessImageModeration::dispatch('comment', $comment->id, $imageUrl);        $comment = Comment::factory()->create([

            'user_id' => $this->user->id,

        // Process the queue            'entity_type' => Trip::class,

        $this->artisan('queue:work', [            'entity_id' => $trip->id,

            '--stop-when-empty' => true,            'content' => 'Test comment',

            '--tries' => 1,        ]);

        ]);

        // Use real external URL for testing

        // Refresh comment from database        $imageUrl = $this->getTestImageUrl(1);

        $comment->refresh();        $comment->update(['images' => [$imageUrl]]);



        // Assertions        // Dispatch the job to queue

        $this->assertNotNull($comment->moderation_result);        ProcessImageModeration::dispatch('comment', $comment->id, $imageUrl);

        $this->assertIsArray($comment->moderation_result);

        $this->assertArrayHasKey('safe', $comment->moderation_result);        // Process the queue

        $this->assertArrayHasKey('adult', $comment->moderation_result);        $this->artisan('queue:work', [

        $this->assertArrayHasKey('porn', $comment->moderation_result);            '--stop-when-empty' => true,

    }            '--tries' => 1,

        ]);

    /** @test */

    public function job_processes_checkpoint_image_moderation()        // Refresh comment from database

    {        $comment->refresh();

        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        $checkpoint = \App\Models\MapCheckpoint::factory()->create([        // Assertions

            'trip_id' => $trip->id,        $this->assertNotNull($comment->moderation_result);

            'user_id' => $this->user->id,        $this->assertIsArray($comment->moderation_result);

        ]);        $this->assertArrayHasKey('safe', $comment->moderation_result);

    }

        // Use real external URL for testing

        $imageUrl = $this->getTestImageUrl(0);    /** @test */

    public function job_processes_checkpoint_image_moderation()

        $image = CheckpointImage::create([    {

            'map_checkpoint_id' => $checkpoint->id,        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

            'user_id' => $this->user->id,        $checkpoint = \App\Models\MapCheckpoint::factory()->create([

            'file_path' => $imageUrl,            'trip_id' => $trip->id,

            'uploaded_at' => now(),            'user_id' => $this->user->id,

        ]);        ]);



        // Dispatch the job to queue        // Use real external URL for testing

        ProcessImageModeration::dispatch('checkpoint_image', $image->id, $imageUrl);        $imageUrl = $this->getTestImageUrl(0);



        // Process the queue        $image = CheckpointImage::create([

        $this->artisan('queue:work', [            'map_checkpoint_id' => $checkpoint->id,

            '--stop-when-empty' => true,            'user_id' => $this->user->id,

            '--tries' => 1,            'file_path' => $imageUrl,

        ]);            'uploaded_at' => now(),

        ]);

        // Refresh image from database

        $image->refresh();        // Dispatch the job to queue

        ProcessImageModeration::dispatch('checkpoint_image', $image->id, $imageUrl);

        // Assertions (checkpoint_images uses 'moderation_results' plural)

        $this->assertNotNull($image->moderation_results);        // Process the queue

        $this->assertIsArray($image->moderation_results);        $this->artisan('queue:work', [

        $this->assertArrayHasKey('safe', $image->moderation_results);            '--stop-when-empty' => true,

    }            '--tries' => 1,

        ]);

    /** @test */

    public function job_handles_missing_image_gracefully()        // Refresh image from database

    {        $image->refresh();

        $place = Place::factory()->create();

        $review = Review::factory()->create([        // Assertions

            'user_id' => $this->user->id,        $this->assertNotNull($image->moderation_results);

            'reviewable_type' => Place::class,        $this->assertIsArray($image->moderation_results);

            'reviewable_id' => $place->id,        $this->assertArrayHasKey('safe', $image->moderation_results);

            'rating' => 5,    }

        ]);

    /** @test */

        $imagePath = "reviews/{$review->id}/nonexistent.jpg";    public function job_flags_inappropriate_content()

        $review->update(['images' => [$imagePath]]);    {

        // Create a review with an external image URL

        // Execute the job directly (not via queue)        $place = Place::factory()->create();

        $job = new ProcessImageModeration('review', $review->id, $imagePath);        $review = Review::factory()->create([

        $job->handle($this->greenEyeService);            'user_id' => $this->user->id,

            'reviewable_type' => Place::class,

        // Review should remain unchanged (no moderation result)            'reviewable_id' => $place->id,

        $review->refresh();            'rating' => 5,

        $this->assertNull($review->moderation_result);        ]);

    }

        // Use real external URL for testing (may or may not be flagged)

    /** @test */        $imageUrl = $this->getTestImageUrl(1);

    public function job_handles_disabled_greeneye_service()        $review->update(['images' => [$imageUrl]]);

    {

        // Temporarily disable Green-Eye service        // Dispatch the job to queue

        config(['services.naver.greeneye.enabled' => false]);        ProcessImageModeration::dispatch('review', $review->id, $imageUrl);



        $place = Place::factory()->create();        // Process the queue

        $review = Review::factory()->create([        $this->artisan('queue:work', [

            'user_id' => $this->user->id,            '--stop-when-empty' => true,

            'reviewable_type' => Place::class,            '--tries' => 1,

            'reviewable_id' => $place->id,        ]);

            'rating' => 5,

        ]);        // Refresh review from database

        $review->refresh();

        $imageUrl = $this->getTestImageUrl(0);

        $review->update(['images' => [$imageUrl]]);        // Assertions

        $this->assertNotNull($review->moderation_result);

        // Execute the job directly        $this->assertIsArray($review->moderation_result);

        $job = new ProcessImageModeration('review', $review->id, $imageUrl);        

        $job->handle($this->greenEyeService);        // Check if moderation was performed (regardless of result)

        $this->assertArrayHasKey('safe', $review->moderation_result);

        // Should complete without error, no moderation result        $this->assertArrayHasKey('reason', $review->moderation_result);

        $review->refresh();    }

        $this->assertNull($review->moderation_result);

    }    /** @test */

    public function job_handles_missing_image_gracefully()

    /** @test */    {

    public function job_processes_multiple_images_for_review()        $place = Place::factory()->create();

    {        $review = Review::factory()->create([

        $place = Place::factory()->create();            'user_id' => $this->user->id,

        $review = Review::factory()->create([            'reviewable_type' => Place::class,

            'user_id' => $this->user->id,            'reviewable_id' => $place->id,

            'reviewable_type' => Place::class,            'rating' => 5,

            'reviewable_id' => $place->id,        ]);

            'rating' => 5,

        ]);        $imagePath = "reviews/{$review->id}/nonexistent.jpg";

        $review->update(['images' => [$imagePath]]);

        // Use multiple external URLs for testing

        $imageUrls = [        // Execute the job (should not throw exception)

            $this->getTestImageUrl(0),        $job = new ProcessImageModeration('review', $review->id, $imagePath);

            $this->getTestImageUrl(1),        $job->handle(app(GreenEyeService::class));

            $this->getTestImageUrl(0), // Reuse first one

        ];        // Review should remain unchanged (no moderation result)

        $review->refresh();

        $review->update(['images' => $imageUrls]);        $this->assertNull($review->moderation_result);

    }

        // Process each image

        foreach ($imageUrls as $imageUrl) {    /** @test */

            ProcessImageModeration::dispatch('review', $review->id, $imageUrl);    public function job_handles_disabled_greeneye_service()

        }    {

        // Temporarily disable Green-Eye service

        // Process the queue        config(['services.naver.greeneye.enabled' => false]);

        $this->artisan('queue:work', [

            '--stop-when-empty' => true,        $place = Place::factory()->create();

            '--tries' => 1,        $review = Review::factory()->create([

        ]);            'user_id' => $this->user->id,

            'reviewable_type' => Place::class,

        // Verify moderation was performed (last image overwrites)            'reviewable_id' => $place->id,

        $review->refresh();            'rating' => 5,

        $this->assertNotNull($review->moderation_result);        ]);

    }

        $imagePath = "reviews/{$review->id}/test.jpg";

    /** @test */        $fakeImage = UploadedFile::fake()->image('test.jpg');

    public function job_updates_is_flagged_based_on_confidence()        Storage::disk('public')->put($imagePath, file_get_contents($fakeImage->getRealPath()));

    {        

        $place = Place::factory()->create();        $review->update(['images' => [$imagePath]]);

        $review = Review::factory()->create([

            'user_id' => $this->user->id,        // Execute the job

            'reviewable_type' => Place::class,        $job = new ProcessImageModeration('review', $review->id, $imagePath);

            'reviewable_id' => $place->id,        $job->handle(app(GreenEyeService::class));

            'rating' => 5,

        ]);        // Should complete without error, no moderation result

        $review->refresh();

        // Use real external URL for testing        $this->assertNull($review->moderation_result);

        $imageUrl = $this->getTestImageUrl(0);    }

        $review->update(['images' => [$imageUrl]]);

    /** @test */

        // Dispatch the job to queue    public function job_processes_multiple_images_for_review()

        ProcessImageModeration::dispatch('review', $review->id, $imageUrl);    {

        $place = Place::factory()->create();

        // Process the queue        $review = Review::factory()->create([

        $this->artisan('queue:work', [            'user_id' => $this->user->id,

            '--stop-when-empty' => true,            'reviewable_type' => Place::class,

            '--tries' => 1,            'reviewable_id' => $place->id,

        ]);            'rating' => 5,

        ]);

        // Refresh review from database

        $review->refresh();        // Use multiple external URLs for testing

        $imageUrls = [

        // Assertions            $this->getTestImageUrl(0),

        $this->assertNotNull($review->moderation_result);            $this->getTestImageUrl(1),

        $this->assertIsBool($review->is_flagged);            $this->getTestImageUrl(0), // Reuse first one

                ];

        // Verify confidence scores are present (based on API response format)

        $this->assertArrayHasKey('safe', $review->moderation_result);        $review->update(['images' => $imageUrls]);

        $this->assertArrayHasKey('adult', $review->moderation_result);

        $this->assertArrayHasKey('normal', $review->moderation_result);        // Process each image

                foreach ($imageUrls as $imageUrl) {

        // If normal confidence is high, should not be flagged            ProcessImageModeration::dispatch('review', $review->id, $imageUrl);

        if ($review->moderation_result['normal'] > 0.5) {        }

            $this->assertFalse($review->is_flagged);

        }        // Process the queue

    }        $this->artisan('queue:work', [

            '--stop-when-empty' => true,

    /** @test */            '--tries' => 1,

    public function green_eye_service_can_check_image_safety_directly()        ]);

    {

        // Test the service directly with a public URL        // Verify moderation was performed

        $imageUrl = $this->getTestImageUrl(0);        $review->refresh();

                $this->assertNotNull($review->moderation_result);

        $result = $this->greenEyeService->checkImageSafety($imageUrl);    }



        // Verify result structure based on API documentation    /** @test */

        $this->assertIsArray($result);    public function job_updates_is_flagged_based_on_confidence()

        $this->assertArrayHasKey('safe', $result);    {

        $this->assertIsBool($result['safe']);        $place = Place::factory()->create();

        $this->assertArrayHasKey('reason', $result);        $review = Review::factory()->create([

        $this->assertIsString($result['reason']);            'user_id' => $this->user->id,

                    'reviewable_type' => Place::class,

        // Verify API response fields are present            'reviewable_id' => $place->id,

        $this->assertArrayHasKey('adult', $result);            'rating' => 5,

        $this->assertArrayHasKey('normal', $result);        ]);

        $this->assertArrayHasKey('porn', $result);

        $this->assertArrayHasKey('sexy', $result);        // Use real external URL for testing

    }        $imageUrl = $this->getTestImageUrl(0);

}        $review->update(['images' => [$imageUrl]]);


        // Dispatch the job to queue
        ProcessImageModeration::dispatch('review', $review->id, $imageUrl);

        // Process the queue
        $this->artisan('queue:work', [
            '--stop-when-empty' => true,
            '--tries' => 1,
        ]);

        // Refresh review from database
        $review->refresh();

        // Assertions
        $this->assertNotNull($review->moderation_result);
        $this->assertIsBool($review->is_flagged);
        
        // is_flagged should be set based on API response
        $this->assertArrayHasKey('safe', $review->moderation_result);
    }
}
