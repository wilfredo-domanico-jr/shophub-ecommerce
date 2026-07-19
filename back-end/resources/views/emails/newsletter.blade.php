<x-mail::message>
@if ($newsletter->image_url)
![{{ $newsletter->subject }}]({{ $newsletter->image_url }})
@endif

# {{ $newsletter->subject }}

{!! nl2br(e($newsletter->body)) !!}

<x-mail::button :url="$shopUrl">
Shop Now
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}

<x-slot:subcopy>
You're receiving this because you subscribed to the ShopHub newsletter. [Unsubscribe]({{ $unsubscribeUrl }})
</x-slot:subcopy>
</x-mail::message>
