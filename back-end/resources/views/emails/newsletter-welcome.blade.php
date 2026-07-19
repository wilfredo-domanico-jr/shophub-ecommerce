<x-mail::message>
# Thanks for subscribing! 🎉

You're on the list. From now on you'll be the first to hear about flash sales, new arrivals, and special offers from ShopHub.

<x-mail::button :url="$shopUrl">
Start Shopping
</x-mail::button>

If you didn't sign up for this, you can safely ignore this email.

Thanks,<br>
{{ config('app.name') }}

<x-slot:subcopy>
Don't want these emails? [Unsubscribe]({{ $unsubscribeUrl }})
</x-slot:subcopy>
</x-mail::message>
