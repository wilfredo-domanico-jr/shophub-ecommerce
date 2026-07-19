@php
// Local uploads are embedded as inline attachments; external URLs are linked.
// (Hand-built HTML instead of markdown because markdown mails can't embed.)
// Styled to match Laravel's default mail theme used by the other ShopHub emails.
$localImage = $newsletter->localImagePath();
$imageSrc = $localImage ? $message->embed($localImage) : $newsletter->image_url;
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $newsletter->subject }}</title>
</head>

<body style="margin:0; padding:0; width:100%; background-color:#edf2f7; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#edf2f7;">
        <tr>
            <td align="center">
                <table role="presentation" cellpadding="0" cellspacing="0" style="width:100%; max-width:570px;">
                    <!-- Header -->
                    <tr>
                        <td align="center" style="padding:25px 0;">
                            <a href="{{ $shopUrl }}" style="font-size:19px; font-weight:bold; color:#3d4852; text-decoration:none;">
                                {{ config('app.name') }}
                            </a>
                        </td>
                    </tr>

                    <!-- Body panel -->
                    <tr>
                        <td style="background-color:#ffffff; border:1px solid #e8e5ef; border-radius:2px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="padding:32px;">
                                        @if ($imageSrc)
                                        <img src="{{ $imageSrc }}" alt="{{ $newsletter->subject }}" width="506" style="width:100%; max-width:506px; border-radius:4px; margin-bottom:24px; display:block;">
                                        @endif

                                        <h1 style="margin:0 0 16px; font-size:18px; font-weight:bold; color:#3d4852;">{{ $newsletter->subject }}</h1>

                                        <p style="margin:0 0 24px; font-size:16px; line-height:1.5em; color:#3d4852;">{!! nl2br(e($newsletter->body)) !!}</p>

                                        <table role="presentation" align="center" cellpadding="0" cellspacing="0" style="margin:0 auto 24px;">
                                            <tr>
                                                <td style="border-radius:4px; background-color:#ff6b35;">
                                                    <a href="{{ $shopUrl }}" style="display:inline-block; padding:8px 18px; font-size:16px; color:#ffffff; text-decoration:none; border-radius:4px;">Shop Now</a>
                                                </td>
                                            </tr>
                                        </table>



                                        <p style="margin:0; font-size:16px; line-height:1.5em; color:#3d4852;">
                                            Thanks,<br>
                                            {{ config('app.name') }}
                                        </p>
                                    </td>
                                </tr>

                                <!-- Subcopy (separator + small print) -->
                                <tr>
                                    <td style="padding:0 32px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid #e8e5ef;">
                                            <tr>
                                                <td style="padding:16px 0 32px;">
                                                    <p style="margin:0; font-size:12px; line-height:1.5em; color:#718096;">
                                                        You're receiving this because you subscribed to the {{ config('app.name') }} newsletter.
                                                        <a href="{{ $unsubscribeUrl }}" style="color:#718096;">Unsubscribe</a>
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center" style="padding:32px;">
                            <p style="margin:0; font-size:12px; color:#b0adc5;">
                                &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>