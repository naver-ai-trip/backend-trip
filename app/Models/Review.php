<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Review Model
 *
 * Represents user reviews for places and checkpoints with ratings and comments.
 * Supports polymorphic relationships to review different entity types.
 *
 * NAVER API Integration:
 * - Comments can be translated via PapagoService
 * - Translations stored separately in Translation model
 * - Original comment always preserved
 */
class Review extends Model
{
    /** @use HasFactory<\Database\Factories\ReviewFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'reviewable_type',
        'reviewable_id',
        'rating',
        'comment',
        'images',
        'moderation_results',
        'is_flagged',
    ];

    protected $casts = [
        'rating' => 'integer',
        'images' => 'array',
        'moderation_results' => 'array',
        'is_flagged' => 'boolean',
    ];

    /**
     * Get the user who created the review
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the reviewable entity (Place or MapCheckpoint)
     */
    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope query to reviews with specific rating
     */
    public function scopeRating($query, int $rating)
    {
        return $query->where('rating', $rating);
    }

    /**
     * Scope query to reviews by specific user
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Check if the review has been flagged by content moderation
     */
    public function isFlagged(): bool
    {
        return $this->is_flagged === true;
    }

    /**
     * Add an image with its moderation results
     */
    public function addImage(string $path, array $moderationResult): void
    {
        $currentImages = $this->images ?? [];
        $currentImages[] = $path;
        
        $this->images = $currentImages;
        $this->moderation_results = $moderationResult;
        
        // Flag if confidence is high for adult content or violence
        $isAdultContent = ($moderationResult['adult']['confidence'] ?? 0) > 0.7;
        $isViolentContent = ($moderationResult['violence']['confidence'] ?? 0) > 0.7;
        
        if ($isAdultContent || $isViolentContent) {
            $this->is_flagged = true;
        }
        
        $this->save();
    }

    /**
     * Get public URLs for all images
     */
    public function getImageUrlsAttribute(): array
    {
        if (empty($this->images)) {
            return [];
        }

        return array_map(function ($path) {
            return \Illuminate\Support\Facades\Storage::disk(config('filesystems.public_disk'))->url($path);
        }, $this->images);
    }
}
