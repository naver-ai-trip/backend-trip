<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Comment extends Model
{
    /** @use HasFactory<\Database\Factories\CommentFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'entity_type',
        'entity_id',
        'content',
        'images',
        'moderation_results',
        'is_flagged',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'entity_id' => 'integer',
            'images' => 'array',
            'moderation_results' => 'array',
            'is_flagged' => 'boolean',
        ];
    }

    /**
     * Get the user who created this comment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the parent commentable entity (Trip, TripDiary, MapCheckpoint).
     */
    public function entity(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Check if the comment has been flagged by content moderation
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
            return \Illuminate\Support\Facades\Storage::url($path);
        }, $this->images);
    }
}
