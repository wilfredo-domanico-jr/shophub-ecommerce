<?php

namespace Tests\Feature;

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class SocialAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.google' => [
                'client_id' => 'google-client-id',
                'client_secret' => 'google-client-secret',
                'redirect' => 'http://127.0.0.1:8000/api/auth/google/callback',
            ],
            'services.facebook' => [
                'client_id' => 'facebook-client-id',
                'client_secret' => 'facebook-client-secret',
                'redirect' => 'http://127.0.0.1:8000/api/auth/facebook/callback',
            ],
            'app.frontend_url' => 'http://localhost:5173',
        ]);
    }

    private function mockSocialiteUser(
        string $provider,
        ?string $email,
        string $id = 'prov-123',
        ?string $name = 'Social User'
    ): void {
        $socialUser = Mockery::mock(SocialiteUser::class);
        $socialUser->shouldReceive('getId')->andReturn($id);
        $socialUser->shouldReceive('getEmail')->andReturn($email);
        $socialUser->shouldReceive('getName')->andReturn($name);
        $socialUser->shouldReceive('getNickname')->andReturn(null);

        Socialite::shouldReceive('driver')->with($provider)->andReturnSelf();
        Socialite::shouldReceive('stateless')->andReturnSelf();
        // once() so sequential mocks (two-provider test) consume in order.
        Socialite::shouldReceive('user')->once()->andReturn($socialUser);
    }

    public function test_redirect_sends_the_user_to_the_provider(): void
    {
        $response = $this->get('/api/auth/google/redirect');

        $response->assertRedirect();
        $this->assertStringContainsString(
            'accounts.google.com',
            $response->headers->get('Location')
        );
    }

    public function test_redirect_returns_404_for_unknown_provider(): void
    {
        $this->get('/api/auth/twitter/redirect')->assertNotFound();
    }

    public function test_redirect_returns_404_when_provider_is_not_configured(): void
    {
        config(['services.google.client_id' => null]);

        $this->get('/api/auth/google/redirect')->assertNotFound();
    }

    public function test_callback_creates_a_new_user_and_social_account(): void
    {
        $this->mockSocialiteUser('google', 'new@example.com');

        $response = $this->get('/api/auth/google/callback');

        $response->assertRedirect();
        $this->assertStringStartsWith(
            'http://localhost:5173/auth/callback?token=',
            $response->headers->get('Location')
        );

        $user = User::where('email', 'new@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNull($user->password);
        $this->assertFalse($user->is_admin);
        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'prov-123',
        ]);
    }

    public function test_callback_links_an_existing_user_by_email(): void
    {
        $existing = User::factory()->create(['email' => 'existing@example.com']);
        $this->mockSocialiteUser('google', 'existing@example.com');

        $response = $this->get('/api/auth/google/callback');

        $response->assertRedirect();
        $this->assertStringStartsWith(
            'http://localhost:5173/auth/callback?token=',
            $response->headers->get('Location')
        );
        $this->assertSame(1, User::count());
        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $existing->id,
            'provider' => 'google',
        ]);
    }

    public function test_callback_logs_in_via_existing_social_account_even_if_email_changed(): void
    {
        $user = User::factory()->create(['email' => 'original@example.com']);
        SocialAccount::create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'prov-123',
        ]);
        // Same provider id, different email at the provider.
        $this->mockSocialiteUser('google', 'changed@example.com');

        $response = $this->get('/api/auth/google/callback');

        $response->assertRedirect();
        $this->assertStringStartsWith(
            'http://localhost:5173/auth/callback?token=',
            $response->headers->get('Location')
        );
        $this->assertSame(1, User::count());
        $this->assertSame(1, SocialAccount::count());
    }

    public function test_callback_without_email_redirects_with_error_and_creates_nothing(): void
    {
        $this->mockSocialiteUser('facebook', null);

        $response = $this->get('/api/auth/facebook/callback');

        $response->assertRedirect();
        $this->assertStringContainsString('error=no_email', $response->headers->get('Location'));
        $this->assertSame(0, User::count());
        $this->assertSame(0, SocialAccount::count());
    }

    public function test_callback_redirects_with_error_when_socialite_throws(): void
    {
        Socialite::shouldReceive('driver')->with('google')->andReturnSelf();
        Socialite::shouldReceive('stateless')->andReturnSelf();
        Socialite::shouldReceive('user')->andThrow(new \Exception('provider down'));

        $response = $this->get('/api/auth/google/callback');

        $response->assertRedirect();
        $this->assertStringContainsString('error=social_failed', $response->headers->get('Location'));
        $this->assertSame(0, User::count());
    }

    public function test_password_login_fails_for_a_social_only_user(): void
    {
        User::factory()->create([
            'email' => 'social@example.com',
            'password' => null,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'social@example.com',
            'password' => 'anything123',
        ]);

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Invalid credentials']);
    }

    public function test_both_providers_can_link_to_one_user(): void
    {
        $this->mockSocialiteUser('google', 'shared@example.com', 'google-1');
        $this->get('/api/auth/google/callback')->assertRedirect();

        $this->mockSocialiteUser('facebook', 'shared@example.com', 'facebook-1');
        $this->get('/api/auth/facebook/callback')->assertRedirect();

        $this->assertSame(1, User::count());
        $this->assertSame(2, SocialAccount::count());
        $user = User::first();
        $this->assertSame(
            ['facebook', 'google'],
            $user->socialAccounts()->orderBy('provider')->pluck('provider')->all()
        );
    }
}
