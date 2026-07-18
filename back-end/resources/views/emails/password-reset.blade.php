<x-mail::message>
# Hi {{ $user->name }},

We received a request to reset the password for your account. Click the button below to choose a new password.

<x-mail::button :url="$resetUrl">
Reset Password
</x-mail::button>

This link will expire in {{ $expireMinutes }} minutes. If you didn't request a password reset, you can safely ignore this email — your password will stay the same.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
