<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    private const PROVIDERS = ['google', 'facebook'];

    public function redirect(string $provider)
    {
        $this->ensureProviderIsAvailable($provider);

        // Stateless: OAuth callbacks are top-level navigations from the
        // provider's domain, so Sanctum never treats them as stateful and
        // there is no session to hold Socialite's state parameter.
        return Socialite::driver($provider)->stateless()->redirect();
    }

    public function callback(string $provider)
    {
        $this->ensureProviderIsAvailable($provider);

        try {
            $socialUser = Socialite::driver($provider)->stateless()->user();
        } catch (\Throwable) {
            return $this->redirectToSpa(['error' => 'social_failed']);
        }

        $email = $socialUser->getEmail();

        // Facebook accounts registered by phone (or that decline the email
        // permission) have no email — never create an email-less user.
        if (! $email) {
            return $this->redirectToSpa(['error' => 'no_email']);
        }

        $user = DB::transaction(function () use ($provider, $socialUser, $email) {
            $account = SocialAccount::where('provider', $provider)
                ->where('provider_id', (string) $socialUser->getId())
                ->first();

            if ($account) {
                return $account->user;
            }

            // Linking by email is only safe because Google and Facebook
            // return provider-verified emails — don't extend this pattern
            // to providers that don't verify them.
            $user = User::where('email', $email)->first()
                ?? User::create([
                    'name' => $socialUser->getName() ?: ($socialUser->getNickname() ?: 'Customer'),
                    'email' => $email,
                    'password' => null,
                ]);

            $user->socialAccounts()->create([
                'provider' => $provider,
                'provider_id' => (string) $socialUser->getId(),
            ]);

            return $user;
        });

        $token = $user->createToken('vue-token')->plainTextToken;

        return $this->redirectToSpa(['token' => $token]);
    }

    private function ensureProviderIsAvailable(string $provider): void
    {
        // Unconfigured providers 404 like unknown ones — no broken flows.
        abort_unless(
            in_array($provider, self::PROVIDERS, true)
                && config("services.$provider.client_id"),
            404
        );
    }

    private function redirectToSpa(array $query)
    {
        return redirect()->away(
            rtrim(config('app.frontend_url'), '/').'/auth/callback?'.http_build_query($query)
        );
    }
}
