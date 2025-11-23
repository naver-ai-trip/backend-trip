<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    /**
     * Redirect to Google OAuth page
     *
     * @OA\Get(
     *     path="/api/auth/google",
     *     summary="Redirect to Google OAuth login page",
     *     tags={"Authentication"},
     *     @OA\Response(
     *         response=200,
     *         description="Redirect URL for Google OAuth",
     *         @OA\JsonContent(
     *             @OA\Property(property="url", type="string", example="https://accounts.google.com/oauth2...")
     *         )
     *     )
     * )
     */
    public function redirectToGoogle(): JsonResponse
    {
        $url = Socialite::driver('google')
            ->stateless()
            ->redirect()
            ->getTargetUrl();

        return response()->json([
            'url' => $url,
        ]);
    }

    /**
     * Handle Google OAuth callback
     *
     * @OA\Get(
     *     path="/api/auth/google/callback",
     *     summary="Handle Google OAuth callback and create/login user",
     *     tags={"Authentication"},
     *     @OA\Parameter(
     *         name="code",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User authenticated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="email", type="string"),
     *                 @OA\Property(property="avatar_path", type="string")
     *             ),
     *             @OA\Property(property="token", type="string", description="Sanctum API token"),
     *             @OA\Property(property="message", type="string", example="Login successful")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="OAuth callback failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unable to authenticate with Google")
     *         )
     *     )
     * )
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            // Find or create user
            $user = User::where('email', $googleUser->getEmail())->first();

            if (!$user) {
                // Create new user from Google account
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'password' => bcrypt(Str::random(32)), // Random password (won't be used)
                    'provider' => 'google',
                    'provider_id' => $googleUser->getId(),
                    'avatar_path' => $googleUser->getAvatar(),
                    'email_verified_at' => now(), // Google verified the email
                ]);

                $message = 'Account created successfully';
            } else {
                // Update existing user's provider info if not set
                if (!$user->provider || !$user->provider_id) {
                    $user->update([
                        'provider' => 'google',
                        'provider_id' => $googleUser->getId(),
                    ]);
                }

                // Update avatar if available
                if ($googleUser->getAvatar() && !$user->avatar_path) {
                    $user->update(['avatar_path' => $googleUser->getAvatar()]);
                }

                $message = 'Login successful';
            }

            // Create Sanctum token for API authentication
            $token = $user->createToken('google-auth')->plainTextToken;

            // Redirect to frontend with user data and token as query parameters
            $frontendUrl = config('app.frontend_url', env('FRONTEND_URL'));
            
            $queryParams = http_build_query([
                'token' => $token,
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar_path' => $user->avatar_path,
                'provider' => $user->provider,
                'message' => $message,
            ]);

            return redirect($frontendUrl . '?' . $queryParams);
        } catch (\Exception $e) {
            // Redirect to frontend with error
            $frontendUrl = config('app.frontend_url', env('FRONTEND_URL'));
            
            $queryParams = http_build_query([
                'error' => 'authentication_failed',
                'message' => 'Unable to authenticate with Google',
                'details' => config('app.debug') ? $e->getMessage() : null,
            ]);

            return redirect($frontendUrl . '/auth/callback?' . $queryParams);
        }
    }
}
