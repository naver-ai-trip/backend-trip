<?php

namespace App\Services\Naver;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * NAVER Green-Eye Service
 * 
 * AI-powered content moderation for images
 * Detects: adult content, violence, inappropriate content
 * 
 * @see https://api.ncloud-docs.com/docs/ai-naver-greeneye
 */
class GreenEyeService
{
    protected string $url;
    protected string $secretKey;
    protected bool $enabled;
    protected int $timeout;

    public function __construct()
    {
        $this->url = config('services.naver.greeneye.url', '');
        $this->secretKey = config('services.naver.greeneye.secret_key', '');
        $this->enabled = config('services.naver.greeneye.enabled', false);
        $this->timeout = config('services.naver.greeneye.timeout', 30);
    }

    /**
     * Check if Green-Eye service is enabled and configured
     */
    public function isEnabled(): bool
    {
        return $this->enabled && !empty($this->url) && !empty($this->secretKey);
    }

    /**
     * Comprehensive image safety check (adult content detection)
     * Green-Eye Custom API returns adult, porn, sexy, and normal confidence scores
     * 
     * @param UploadedFile|string $image File or path
     * @param float $threshold Confidence threshold (0.0-1.0), default 0.7
     * @return array ['safe' => true/false, 'adult' => [...], 'porn' => [...], 'sexy' => [...], 'reason' => '...']
     */
    public function checkImageSafety($image, float $threshold = 0.7): array
    {
        if (!$this->isEnabled()) {
            return [
                'safe' => true,
                'reason' => 'Content moderation disabled',
                'adult' => null,
                'porn' => null,
                'sexy' => null,
                'normal' => null,
            ];
        }

        // API call returns adult content analysis (adult, porn, sexy, normal)
        $result = $this->analyzeImage($image, 'greeneye');

        if (!$result) {
            return [
                'safe' => true,
                'reason' => 'Analysis failed, allowing content by default',
                'adult' => null,
                'porn' => null,
                'sexy' => null,
                'normal' => null,
            ];
        }

        // Extract confidence scores
        $adultScore = $result['adult']['confidence'] ?? 0.0;
        $pornScore = $result['porn']['confidence'] ?? 0.0;
        $sexyScore = $result['sexy']['confidence'] ?? 0.0;
        $normalScore = $result['normal']['confidence'] ?? 1.0;

        // Content is safe if normal confidence is higher than all inappropriate categories
        $isSafe = $normalScore > max($adultScore, $pornScore, $sexyScore);
        
        $reasons = [];
        if (!$isSafe) {
            if ($adultScore > $normalScore) {
                $reasons[] = 'adult content';
            }
            if ($pornScore > $normalScore) {
                $reasons[] = 'pornographic content';
            }
            if ($sexyScore > $normalScore) {
                $reasons[] = 'sexually suggestive content';
            }
        }

        $reason = $isSafe 
            ? 'Content passed safety checks' 
            : ucfirst(implode(', ', $reasons)) . ' detected';

        return [
            'safe' => $isSafe,
            'reason' => $reason,
            'adult' => $adultScore,
            'porn' => $pornScore,
            'sexy' => $sexyScore,
            'normal' => $normalScore,
        ];
    }

    /**
     * Analyze image using Green-Eye API
     * 
     * @param UploadedFile|string $image Path to image file in storage or external URL
     * @param string $type 'adult' or 'violence' or 'greeneye' (both)
     * @return array|null
     */
    protected function analyzeImage($image, string $type): ?array
    {
        try {
            // Get public URL for the image
            $imageUrl = $this->getImageUrl($image);
            
            // Generate unique request ID
            $requestId = uniqid('greeneye_', true);
            
            Log::info('Green-Eye API request', [
                'url' => $this->url,
                'image_url' => $imageUrl,
                'request_id' => $requestId,
            ]);
            
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'X-GREEN-EYE-SECRET' => $this->secretKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($this->url, [
                    'version' => 'V1',
                    'requestId' => $requestId,
                    'timestamp' => now()->timestamp * 1000, // milliseconds
                    'images' => [
                        [
                            'name' => $this->getImageFilename($image),
                            'url' => $imageUrl,
                        ]
                    ],
                ]);

            if ($response->successful()) {
                $result = $response->json();
                
                Log::info('Green-Eye API response', [
                    'request_id' => $requestId,
                    'status' => $response->status(),
                    'result' => $result,
                ]);
                
                // Extract the first image result from Green-Eye response
                // Response format: {"images": [{"result": {"adult": {...}, "normal": {...}, "violence": {...}}}]}
                if (isset($result['images'][0]['result'])) {
                    return $result['images'][0]['result'];
                }
                
                return $result;
            }

            Log::error('Green-Eye API error', [
                'request_id' => $requestId,
                'type' => $type,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Green-Eye service exception', [
                'type' => $type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Get API endpoint URL
     * Green-Eye Custom API returns both adult and violence results in a single call
     */
    protected function getEndpointUrl(): string
    {
        return $this->url;
    }

    /**
     * Get image content for upload
     */
    protected function getImageContent($image): string
    {
        if ($image instanceof UploadedFile) {
            return file_get_contents($image->getRealPath());
        }

        return file_get_contents($image);
    }

    /**
     * Get image filename
     */
    protected function getImageFilename($image): string
    {
        if ($image instanceof UploadedFile) {
            return $image->getClientOriginalName();
        }

        return basename($image);
    }

    /**
     * Get public URL for the image
     * Converts storage path to accessible public URL
     *
     * @param string|UploadedFile $image
     * @return string
     */
    protected function getImageUrl($image): string
    {
        // If it's already a full URL (http:// or https:// or blob:), return as-is
        if (is_string($image) && (
            str_starts_with($image, 'http://') ||
            str_starts_with($image, 'https://') ||
            str_starts_with($image, 'blob:')
        )) {
            return $image;
        }

        // If it's a file path in storage, convert to public URL using Storage::url()
        if (is_string($image)) {
            return Storage::url($image);
        }

        // If it's an UploadedFile, it must be stored first
        throw new \InvalidArgumentException('UploadedFile must be stored before analysis');
    }
}
