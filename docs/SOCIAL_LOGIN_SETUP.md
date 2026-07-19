# Social Login Setup (Google & Facebook)

> ⚠️ **Accurate as of July 19, 2026.** Google Cloud Console and Meta for Developers reorganize their UIs regularly, so the exact menu names and click paths below may have changed by the time you read this. The concepts (Client ID, Client Secret, registered redirect URIs) are stable — if a step doesn't match, search the provider's current docs for the equivalent setting.

ShopHub supports "Continue with Google" and "Continue with Facebook" on `/login` and `/register`. The buttons only appear for providers whose credentials are configured — with no credentials set, the feature is invisible and nothing breaks.

## How it works (short version)

Registering your app with a provider gives you a **Client ID** (public identifier) and a **Client Secret** (a password only the backend uses to exchange the one-time OAuth `code` for the user's profile — never expose it to the frontend). You also register an exact **redirect URI** — the only URL the provider will send users back to. If the URI in a request doesn't match a registered one byte-for-byte, the provider rejects it with `redirect_uri_mismatch`.

The flow: SPA button → `GET /api/auth/{provider}/redirect` → provider consent screen → back to `GET /api/auth/{provider}/callback` → the backend finds or creates the user (linking by verified email) and issues the same Sanctum token as a password login → redirect to the SPA's `/auth/callback`, which stores the token and finishes sign-in.

## Google

1. Go to https://console.cloud.google.com/ and create a project (e.g. `shophub-dev`), then select it.
2. **APIs & Services → OAuth consent screen**: User Type **External**; app name `ShopHub`; your email for support and developer contact. The default scopes (email, profile, openid) are all that's needed.
   - The app starts in **Testing** status: only accounts you add under **Test users** can sign in. Add your own Gmail. (Publishing later doesn't require Google's verification review for these basic scopes.)
3. **APIs & Services → Credentials → Create Credentials → OAuth client ID**:
   - Application type: **Web application**.
   - Authorized JavaScript origins: leave empty (server-side flow).
   - Authorized redirect URIs — add both:
     - `http://127.0.0.1:8000/api/auth/google/callback`
     - `http://localhost:8000/api/auth/google/callback`
4. Copy the **Client ID** and **Client secret** into `back-end/.env` as `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET`.

Note: Google only allows plain `http://` redirect URIs for localhost/127.0.0.1 — a deployed app needs an `https://` URI.

## Facebook

1. Go to https://developers.facebook.com/ → **My Apps → Create App** → use case **"Authenticate and request data from users with Facebook Login"**. Don't pick a Business-type app — those only offer business scopes and can't request `email`.
2. Add the **Facebook Login** product (if the use case didn't already) → **Facebook Login → Settings** → **Valid OAuth Redirect URIs**:
   - `http://localhost:8000/api/auth/facebook/callback`
3. **Grant the `email` permission** (skipping this causes an "Invalid Scopes: email" error): left sidebar → **Use cases** → your Facebook Login use case → **Customize** → under Permissions, click **Add** next to `email` (`public_profile` is already granted).
4. **App Settings → Basic**: copy **App ID** → `FACEBOOK_CLIENT_ID` and **App Secret** → `FACEBOOK_CLIENT_SECRET`.
5. Caveats:
   - **Use `localhost`, not `127.0.0.1`**, for Facebook's redirect URI. Facebook enforces HTTPS on everything except literal `localhost` in Development Mode — with `127.0.0.1` you get "ShopHub isn't using a secure connection" and login is blocked. (Google accepts either host.)
   - In **Development Mode** (the default) only people with a role on the app can log in — add accounts under **App Roles → Testers** (they must accept the invite).
   - Going **Live** requires a Privacy Policy URL and Data Deletion instructions URL, and enforces HTTPS redirect URIs. For a local API that needs real HTTPS, a tunnel like `ngrok http 8000` works — register the tunnel's `https://` callback URL.
   - Facebook accounts registered with a phone number may have **no email** — ShopHub rejects those with a friendly error rather than creating an email-less account.

## Environment variables

In `back-end/.env` (see `back-end/.env.example`):

```env
FRONTEND_URL=http://localhost:5173

GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=http://127.0.0.1:8000/api/auth/google/callback

FACEBOOK_CLIENT_ID=
FACEBOOK_CLIENT_SECRET=
FACEBOOK_REDIRECT_URI=http://localhost:8000/api/auth/facebook/callback
```

The redirect URI must match a registered one exactly (Facebook's must use `localhost` — see the caveats above). `php artisan serve` answers on both `127.0.0.1:8000` and `localhost:8000`, so both hosts work locally. Leave a provider's values empty to hide its button entirely.

## Behavior details

- **Account linking:** a social login whose email matches an existing account links to it (one customer can have a password, Google, and Facebook all on one account). This is safe because Google and Facebook only return verified emails.
- **Social-only accounts** have no password; the password login form correctly rejects them. They can set a password later via the forgot-password flow.
- **Admins** who social-log-in with a matching email get the same access their password login grants — nothing more.

## Known limitations (accepted for now)

- The token briefly transits as a URL query param to the SPA; the callback page scrubs it from history immediately. A one-time exchange code would remove it from URLs entirely — a possible future hardening.
- The stateless flow skips OAuth's `state` CSRF check (there's no session to hold it). Residual risk is low-impact login-CSRF.
