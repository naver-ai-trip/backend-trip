<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class SocialAuthControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_redirects_to_google_oauth_page()
    {
        $response = $this->getJson('/api/auth/google');

        $response->assertOk()
            ->assertJsonStructure(['url'])
            ->assertJsonPath('url', function ($url) {
                return str_contains($url, 'accounts.google.com');
            });
    }

    /** @test */
    public function it_creates_new_user_from_google_account()
    {
        $googleUser = Mockery::mock(SocialiteUser::class);
        $googleUser->shouldReceive('getId')->andReturn('google-123');
        $googleUser->shouldReceive('getName')->andReturn('John Doe');
        $googleUser->shouldReceive('getEmail')->andReturn('john@example.com');
        $googleUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg');

        Socialite::shouldReceive('driver->stateless->user')->andReturn($googleUser);

        $response = $this->getJson('/api/auth/google/callback?code=test-code');

        $response->assertOk()
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'name',
                    'email',
                    'avatar_path',
                    'provider',
                ],
                'token',
                'message',
            ])
            ->assertJsonFragment([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'provider' => 'google',
                'message' => 'Account created successfully',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'provider' => 'google',
            'provider_id' => 'google-123',
        ]);

        $user = User::where('email', 'john@example.com')->first();
        $this->assertNotNull($user->email_verified_at); // Email auto-verified by Google
    }

    /** @test */
    public function it_logs_in_existing_user_with_google_account()
    {
        $existingUser = User::factory()->create([
            'email' => 'john@example.com',
            'provider' => 'google',
            'provider_id' => 'google-123',
        ]);

        $googleUser = Mockery::mock(SocialiteUser::class);
        $googleUser->shouldReceive('getId')->andReturn('google-123');
        $googleUser->shouldReceive('getName')->andReturn('John Doe');
        $googleUser->shouldReceive('getEmail')->andReturn('john@example.com');
        $googleUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg');

        Socialite::shouldReceive('driver->stateless->user')->andReturn($googleUser);

        $response = $this->getJson('/api/auth/google/callback?code=test-code');

        $response->assertOk()
            ->assertJsonFragment([
                'id' => $existingUser->id,
                'email' => 'john@example.com',
                'message' => 'Login successful',
            ]);

        // Verify token was generated
        $this->assertArrayHasKey('token', $response->json());
        $this->assertNotEmpty($response->json('token'));
    }

    /** @test */
    public function it_updates_existing_user_without_provider_info()
    {
        $existingUser = User::factory()->create([
            'email' => 'john@example.com',
            'provider' => null,
            'provider_id' => null,
        ]);

        $googleUser = Mockery::mock(SocialiteUser::class);
        $googleUser->shouldReceive('getId')->andReturn('google-123');
        $googleUser->shouldReceive('getName')->andReturn('John Doe');
        $googleUser->shouldReceive('getEmail')->andReturn('john@example.com');
        $googleUser->shouldReceive('getAvatar')->andReturn(null);

        Socialite::shouldReceive('driver->stateless->user')->andReturn($googleUser);

        $response = $this->getJson('/api/auth/google/callback?code=test-code');

        $response->assertOk();

        $existingUser->refresh();
        $this->assertEquals('google', $existingUser->provider);
        $this->assertEquals('google-123', $existingUser->provider_id);
    }

    /** @test */
    public function it_updates_user_avatar_if_available()
    {
        $existingUser = User::factory()->create([
            'email' => 'john@example.com',
            'provider' => 'google',
            'provider_id' => 'google-123',
            'avatar_path' => null,
        ]);

        $googleUser = Mockery::mock(SocialiteUser::class);
        $googleUser->shouldReceive('getId')->andReturn('google-123');
        $googleUser->shouldReceive('getName')->andReturn('John Doe');
        $googleUser->shouldReceive('getEmail')->andReturn('john@example.com');
        $googleUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg');

        Socialite::shouldReceive('driver->stateless->user')->andReturn($googleUser);

        $response = $this->getJson('/api/auth/google/callback?code=test-code');

        $response->assertOk();

        $existingUser->refresh();
        $this->assertEquals('https://example.com/avatar.jpg', $existingUser->avatar_path);
    }

    /** @test */
    public function it_does_not_override_existing_avatar()
    {
        $existingUser = User::factory()->create([
            'email' => 'john@example.com',
            'provider' => 'google',
            'provider_id' => 'google-123',
            'avatar_path' => 'https://existing.com/avatar.jpg',
        ]);

        $googleUser = Mockery::mock(SocialiteUser::class);
        $googleUser->shouldReceive('getId')->andReturn('google-123');
        $googleUser->shouldReceive('getName')->andReturn('John Doe');
        $googleUser->shouldReceive('getEmail')->andReturn('john@example.com');
        $googleUser->shouldReceive('getAvatar')->andReturn('https://example.com/new-avatar.jpg');

        Socialite::shouldReceive('driver->stateless->user')->andReturn($googleUser);

        $response = $this->getJson('/api/auth/google/callback?code=test-code');

        $response->assertOk();

        $existingUser->refresh();
        $this->assertEquals('https://existing.com/avatar.jpg', $existingUser->avatar_path);
    }

    /** @test */
    public function it_returns_error_when_google_oauth_fails()
    {
        Socialite::shouldReceive('driver->stateless->user')
            ->andThrow(new \Exception('OAuth failed'));

        $response = $this->getJson('/api/auth/google/callback?code=invalid-code');

        $response->assertStatus(400)
            ->assertJsonFragment([
                'message' => 'Unable to authenticate with Google',
            ]);
    }

    /** @test */
    public function it_generates_sanctum_token_for_authentication()
    {
        $googleUser = Mockery::mock(SocialiteUser::class);
        $googleUser->shouldReceive('getId')->andReturn('google-123');
        $googleUser->shouldReceive('getName')->andReturn('John Doe');
        $googleUser->shouldReceive('getEmail')->andReturn('john@example.com');
        $googleUser->shouldReceive('getAvatar')->andReturn(null);

        Socialite::shouldReceive('driver->stateless->user')->andReturn($googleUser);

        $response = $this->getJson('/api/auth/google/callback?code=test-code');

        $response->assertOk();

        $token = $response->json('token');
        $this->assertNotEmpty($token);

        // Verify token works for authenticated routes
        $user = User::where('email', 'john@example.com')->first();
        $authenticatedResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/trips');

        $authenticatedResponse->assertOk();
    }

    /** @test */
    public function it_sets_random_password_for_google_users()
    {
        $googleUser = Mockery::mock(SocialiteUser::class);
        $googleUser->shouldReceive('getId')->andReturn('google-123');
        $googleUser->shouldReceive('getName')->andReturn('John Doe');
        $googleUser->shouldReceive('getEmail')->andReturn('john@example.com');
        $googleUser->shouldReceive('getAvatar')->andReturn(null);

        Socialite::shouldReceive('driver->stateless->user')->andReturn($googleUser);

        $response = $this->getJson('/api/auth/google/callback?code=test-code');

        $response->assertOk();

        $user = User::where('email', 'john@example.com')->first();
        $this->assertNotNull($user->password);
        $this->assertNotEmpty($user->password);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
