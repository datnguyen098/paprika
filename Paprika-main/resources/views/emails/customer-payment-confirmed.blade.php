<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('emails.customer_payment_confirmed.subject', ['code' => $order->code]) }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f0fdf4;font-family:Arial,Helvetica,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f0fdf4;padding:24px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">

                    {{-- Header --}}
                    <tr>
                        <td style="background-color:#059669;padding:28px 36px;border-radius:12px 12px 0 0;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>
                                        <p style="margin:0;font-size:26px;font-weight:bold;color:#ffffff;letter-spacing:-0.5px;">Paprika</p>
                                        <p style="margin:6px 0 0;font-size:13px;color:#a7f3d0;">{{ __('emails.customer_payment_confirmed.title') }}</p>
                                    </td>
                                    <td align="right" style="vertical-align:top;">
                                        <span style="display:inline-block;background-color:#ffffff;color:#059669;font-size:11px;font-weight:bold;padding:5px 12px;border-radius:20px;text-transform:uppercase;letter-spacing:0.5px;">
                                            {{ __('emails.customer_payment_confirmed.badge') }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Greeting --}}
@php
    $prevLocale = app()->getLocale();
    app()->setLocale($order->locale ?? $prevLocale);
@endphp                    <tr>
                        <td style="background-color:#ffffff;padding:28px 36px 0;border-left:1px solid #d1fae5;border-right:1px solid #d1fae5;">
                            <p style="margin:0;font-size:15px;color:#1e293b;line-height:1.6;">
                                {{ __('emails.customer_payment_confirmed.greeting', ['name' => $order->customer_name]) }}
                            </p>
                            <p style="margin:8px 0 0;font-size:14px;color:#64748b;line-height:1.6;">
                                {{ __('emails.customer_payment_confirmed.intro', ['amount' => format_money($order->total)]) }}
                            </p>
                        </td>
                    </tr>

                    {{-- Order code + time + transaction --}}
                    <tr>
                        <td style="background-color:#ffffff;padding:0 36px 24px;border-left:1px solid #d1fae5;border-right:1px solid #d1fae5;">
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f0fdf4;border-radius:10px;padding:16px 20px;">
                                <tr>
                                    <td>
                                        <p style="margin:0;font-size:11px;color:#64748b;text-transform:uppercase;letter-spacing:0.8px;">{{ __('emails.customer_payment_confirmed.order_code') }}</p>
                                        <p style="margin:4px 0 0;font-size:24px;font-weight:bold;color:#059669;letter-spacing:-0.5px;">#{{ $order->code }}</p>
                                    </td>
                                    <td align="right">
                                        <p style="margin:0;font-size:11px;color:#64748b;text-transform:uppercase;letter-spacing:0.8px;">{{ __('emails.customer_payment_confirmed.transaction') }}</p>
                                        <p style="margin:4px 0 0;font-size:13px;color:#1e293b;font-family:monospace;">{{ $transactionCode ?: '-' }}</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Order summary --}}
                    <tr>
                        <td style="background-color:#ffffff;padding:0 36px 24px;border-left:1px solid #d1fae5;border-right:1px solid #d1fae5;">
                            <p style="margin:0 0 10px;font-size:13px;font-weight:bold;color:#059669;text-transform:uppercase;letter-spacing:0.5px;border-bottom:2px solid #d1fae5;padding-bottom:8px;">
                                {{ __('emails.customer_payment_confirmed.order_summary') }}
                            </p>
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="padding:3px 0;font-size:13px;color:#64748b;width:130px;">{{ __('emails.customer_payment_confirmed.fulfillment') }}</td>
                                    <td style="padding:3px 0;font-size:13px;color:#1e293b;font-weight:600;">
                                        {{ $order->fulfillment_method === 'pickup' ? __('emails.customer_payment_confirmed.pickup') : __('emails.customer_payment_confirmed.delivery') }}
                                    </td>
                                </tr>
                                @if($order->fulfillment_method === 'delivery' && $order->delivery_address)
                                <tr>
                                    <td style="padding:3px 0;font-size:13px;color:#64748b;">{{ __('emails.customer_payment_confirmed.delivery_address') }}</td>
                                    <td style="padding:3px 0;font-size:13px;color:#1e293b;">{{ $order->delivery_address }}</td>
                                </tr>
                                @endif
                                @if($order->branch)
                                <tr>
                                    <td style="padding:3px 0;font-size:13px;color:#64748b;">{{ $order->fulfillment_method === 'pickup' ? __('emails.customer_payment_confirmed.pickup_location') : __('emails.customer_payment_confirmed.processing_branch') }}</td>
                                    <td style="padding:3px 0;font-size:13px;color:#1e293b;">{{ $order->branch->name }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <td style="padding:12px 0 3px;font-size:16px;font-weight:bold;color:#059669;border-top:2px solid #d1fae5;" colspan="2">
                                        {{ __('emails.customer_payment_confirmed.total_paid', ['amount' => format_money($order->total)]) }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Items (abbreviated) --}}
                    <tr>
                        <td style="background-color:#ffffff;padding:0 36px 28px;border-left:1px solid #d1fae5;border-right:1px solid #d1fae5;">
                            <p style="margin:0 0 10px;font-size:13px;font-weight:bold;color:#059669;text-transform:uppercase;letter-spacing:0.5px;border-bottom:2px solid #d1fae5;padding-bottom:8px;">{{ __('emails.customer_payment_confirmed.items') }}</p>
                            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                                <thead>
                                    <tr style="background-color:#f0fdf4;">
                                        <th style="padding:9px 12px;text-align:left;font-size:11px;color:#64748b;border-bottom:1px solid #d1fae5;letter-spacing:0.5px;text-transform:uppercase;" align="left">{{ __('emails.customer_payment_confirmed.dish') }}</th>
                                        <th style="padding:9px 12px;text-align:center;font-size:11px;color:#64748b;border-bottom:1px solid #d1fae5;letter-spacing:0.5px;text-transform:uppercase;" align="center">{{ __('emails.customer_payment_confirmed.qty') }}</th>
                                        <th style="padding:9px 12px;text-align:right;font-size:11px;color:#64748b;border-bottom:1px solid #d1fae5;letter-spacing:0.5px;text-transform:uppercase;" align="right">{{ __('emails.customer_payment_confirmed.line_total') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($order->items as $item)
                                    <tr>
                                        <td style="padding:8px 12px;font-size:13px;color:#1e293b;border-bottom:1px solid #f1f5f9;" align="left">{{ $item->quantity }}x {{ $item->dish_name }}</td>
                                        <td style="padding:8px 12px;font-size:13px;color:#1e293b;text-align:center;border-bottom:1px solid #f1f5f9;" align="center">{{ $item->quantity }}</td>
                                        <td style="padding:8px 12px;font-size:13px;color:#1e293b;font-weight:600;text-align:right;border-bottom:1px solid #f1f5f9;" align="right">{{ format_money($item->line_total) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background-color:#f0fdf4;padding:24px 36px;border:1px solid #d1fae5;border-radius:0 0 12px 12px;" align="center">
@php
    $trackUrl = match ($order->locale ?: app()->getLocale()) {
        'vi' => url("/vi/don-hang/{$order->code}"),
        'el' => url("/el/parangelia/{$order->code}"),
        default => url("/en/order/{$order->code}"),
    };
@endphp

<p style="margin:0 0 14px;font-size:13px;color:#64748b;line-height:1.6;">
    {{ __('emails.customer_payment_confirmed.track_note') }}
</p>
<p style="margin:0 0 18px;">
    <a href="{{ $trackUrl }}" style="display:inline-block;background-color:#059669;color:#ffffff;text-decoration:none;font-size:13px;font-weight:800;padding:12px 22px;border-radius:10px;text-transform:uppercase;letter-spacing:0.6px;">
        {{ __('emails.customer_payment_confirmed.track_cta') }}
    </a>
</p>

<p style="margin:0 0 8px;font-size:13px;color:#64748b;line-height:1.6;">
                                {{ __('emails.customer_payment_confirmed.support') }}<br>
                                <a href="tel:+30XXXXXXXXX" style="color:#059669;text-decoration:none;font-weight:bold;">{{ $order->branch?->phone ?? setting('contact_phone', '') }}</a>
                            </p>
                            <p style="margin:0;font-size:12px;color:#94a3b8;">{{ __('emails.customer_payment_confirmed.team') }}</p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
@php app()->setLocale($prevLocale); @endphp
</html>
