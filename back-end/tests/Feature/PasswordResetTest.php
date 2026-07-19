<?php

namespace Tests\Feature;

use App\Mail\PasswordResetMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_queues_mail_for_known_email(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'juan@example.com']);

        $response = $this->postJson('/api/forgot-password', [
            'email' => 'juan@example.com',
        ]);

        $response->assertOk();

        Mail::assertQueued(PasswordResetMail::class, function (PasswordResetMail $mail) use ($user) {
            return $mail->hasTo($user->email)
                && str_contains($mail->resetUrl, '/reset-password?token=');
        });
    }

    public function test_forgot_password_sends_nothing_for_demo_account_in_demo_mode(): void
    {
        Mail::fake();
        config(['demo.enabled' => true, 'demo.customer_email' => 'demo-customer@example.com']);
        User::factory()->create(['email' => 'demo-customer@example.com']);

        $response = $this->postJson('/api/forgot-password', [
            'email' => 'demo-customer@example.com',
        ]);

        // Same generic response as everyone else, but no reset token is minted.
        $response->assertOk();
        Mail::assertNothingQueued();
    }

    public function test_reset_is_rejected_for_demo_account_in_demo_mode(): void
    {
        config(['demo.enabled' => true, 'demo.customer_email' => 'demo-customer@example.com']);
        $user = User::factory()->create(['email' => 'demo-customer@example.com']);
        $token = Password::broker()->createToken($user);

        $response = $this->postJson('/api/reset-password', [
            'token' => $token,
            'email' => 'demo-customer@example.com',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'Password changes are disabled for the shared demo account.');
    }

    public function test_forgot_password_returns_generic_response_for_unknown_email(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/forgot-password', [
            'email' => 'nobody@example.com',
        ]);

        $response->assertOk();
        $response->assertJsonPath('message', 'If that email exists, a reset link has been sent.');

        Mail::assertNothingQueued();
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        $user = User::factory()->create(['email' => 'juan@example.com']);
        $user->createToken('vue-token');

        $token = Password::broker()->createToken($user);

        $response = $this->postJson('/api/reset-password', [
            'token' => $token,
            'email' => 'juan@example.com',
            'password' => 'newsecret123',
            'password_confirmation' => 'newsecret123',
        ]);

        $response->assertOk();

        $user->refresh();
        $this->assertTrue(Hash::check('newsecret123', $user->password));
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_reset_fails_with_invalid_token(): void
    {
        $user = User::factory()->create(['email' => 'juan@example.com']);

        $response = $this->postJson('/api/reset-password', [
            'token' => 'not-a-real-token',
            'email' => 'juan@example.com',
            'password' => 'newsecret123',
            'password_confirmation' => 'newsecret123',
        ]);

        $response->assertUnprocessable();
    }
}
