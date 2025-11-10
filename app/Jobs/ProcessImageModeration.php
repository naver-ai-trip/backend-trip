<?php

namespace App\Jobs;

use App\Models\Review;
use App\Models\Comment;
use App\Models\CheckpointImage;
use App\Services\Naver\GreenEyeService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessImageModeration implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 5;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $modelType,
        public int $modelId,
        public string $imagePath
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(GreenEyeService $greenEyeService): void
    {
        try {
            // Check if Green-Eye service is enabled
            if (!$greenEyeService->isEnabled()) {
                Log::warning('Green-Eye service is disabled, skipping moderation', [
                    'model_type' => $this->modelType,
                    'model_id' => $this->modelId,
                ]);
                return;
            }

            // Determine if this is an external URL or storage path
            $isExternalUrl = str_starts_with($this->imagePath, 'http://') ||
                           str_starts_with($this->imagePath, 'https://');

            // Only verify storage existence for local paths
            if (!$isExternalUrl) {
                if (!Storage::disk(config('filesystems.public_disk'))->exists($this->imagePath)) {
                    Log::error('Image file not found in storage', [
                        'model_type' => $this->modelType,
                        'model_id' => $this->modelId,
                        'path' => $this->imagePath,
                    ]);
                    return;
                }
            }

            Log::info('Starting image moderation', [
                'model_type' => $this->modelType,
                'model_id' => $this->modelId,
                'is_external_url' => $isExternalUrl,
                'path' => $this->imagePath,
            ]);

            // Analyze the image for safety (adult + violence)
            // Service handles both storage paths and external URLs
            $result = $greenEyeService->checkImageSafety($this->imagePath, 0.7);

            // Determine if content should be flagged
            $isFlagged = !$result['safe'];

            // Prepare moderation result
            $moderationResult = [
                'safe' => $result['safe'],
                'reason' => $result['reason'],
                'adult' => $result['adult'] ?? 0.0,
                'porn' => $result['porn'] ?? 0.0,
                'sexy' => $result['sexy'] ?? 0.0,
                'normal' => $result['normal'] ?? 1.0,
            ];

            // Update the model with moderation results
            $this->updateModel($isFlagged, $moderationResult);

            Log::info('Image moderation completed successfully', [
                'model_type' => $this->modelType,
                'model_id' => $this->modelId,
                'is_flagged' => $isFlagged,
                'safe' => $result['safe'],
                'reason' => $result['reason'],
            ]);

        } catch (\Exception $e) {
            Log::error('Image moderation failed with exception', [
                'model_type' => $this->modelType,
                'model_id' => $this->modelId,
                'path' => $this->imagePath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Update the model with moderation results.
     */
    protected function updateModel(bool $isFlagged, array $moderationResult): void
    {
        $model = match ($this->modelType) {
            'review' => Review::find($this->modelId),
            'comment' => Comment::find($this->modelId),
            'checkpoint_image' => CheckpointImage::find($this->modelId),
            default => null,
        };

        if (!$model) {
            Log::error('Model not found for moderation update', [
                'model_type' => $this->modelType,
                'model_id' => $this->modelId,
            ]);
            return;
        }

        // All models (Review, Comment, CheckpointImage) use 'moderation_results' (plural)
        $updated = $model->update([
            'is_flagged' => $isFlagged,
            'moderation_results' => $moderationResult,
        ]);

        Log::info('Model updated with moderation results', [
            'model_type' => $this->modelType,
            'model_id' => $this->modelId,
            'updated' => $updated,
            'is_flagged' => $isFlagged,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Image moderation job failed permanently', [
            'model_type' => $this->modelType,
            'model_id' => $this->modelId,
            'error' => $exception->getMessage(),
        ]);
    }
}
