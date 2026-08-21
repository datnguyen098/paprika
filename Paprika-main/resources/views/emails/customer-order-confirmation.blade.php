<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('emails.customer_order_confirmation.subject', ['code' => $order->code]) }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f0fdf4;font-family:Arial,Helvetica,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f0fdf4;padding:24px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">

                    {{-- Header --}}
                    <tr>
                        <td style="background-color:#064E3B;padding:28px 36px;border-radius:12px 12px 0 0;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>
                                        <p style="margin:0;font-size:26px;font-weight:bold;color:#ffffff;letter-spacing:-0.5px;">Paprika</p>
                                        <p style="margin:6px 0 0;font-size:13px;color:#a7f3d0;">{{ __('emails.customer_order_confirmation.title') }}</p>
                                    </td>
                                    <td align="right" style="vertical-align:top;">
                                        <span style="display:inline-block;background-color:#059669;color:#ffffff;font-size:11px;font-weight:bold;padding:5px 12px;border-radius:20px;text-transform:uppercase;letter-spacing:0.5px;">
                                            {{ $order->status === 'pending'
                                                ? __('emails.customer_order_confirmation.status_pending')
                                                : ($order->status === 'confirmed'
                                                    ? __('emails.customer_order_confirmation.status_confirmed')
                                                    : $order->statusLabel())
                                            }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    @php
    $prevLocale = app()->getLocale();
    app()->setLocale($order->locale ?? $prevLocale);
@endphp

{{-- Greeting --}}
                    <tr>
                        <td style="background-color:#ffffff;padding:28px 36px 0;border-left:1px solid #d1fae5;border-right:1px solid #d1fae5;">
                            <p style="margin:0;font-size:15px;color:#1e293b;line-height:1.6;">
                                {{ __('emails.customer_order_confirmation.greeting', ['name' => $order->customer_name]) }}
                            </p>
                            <p style="margin:8px 0 0;font-size:14px;color:#64748b;line-height:1.6;">
                                {{ __('emails.customer_order_confirmation.intro') }}
                            </p>
                        </td>
                    </tr>

                    {{-- Order code + time --}}
                    <tr>
                        <td style="background-color:#ffffff;padding:0 36px 24px;border-left:1px solid #d1fae5;border-right:1px solid #d1fae5;">
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f0fdf4;border-radius:10px;padding:16px 20px;">
                                <tr>
                                    <td>
                                        <p style="margin:0;font-size:11px;color:#64748b;text-transform:uppercase;letter-spacing:0.8px;">{{ __('emails.customer_order_confirmation.order_code') }}</p>
                                        <p style="margin:4px 0 0;font-size:24px;font-weight:bold;color:#064E3B;letter-spacing:-0.5px;">#{{ $order->code }}</p>
                                    </td>
                                    <td align="right">
                                        <p style="margin:0;font-size:11px;color:#64748b;text-transform:uppercase;letter-spacing:0.8px;">{{ __('emails.customer_order_confirmation.order_date') }}</p>
                                        <p style="margin:4px 0 0;font-size:14px;color:#1e293b;">{{ business_time($order->created_at, $order->branch)?->format('d/m/Y') }} {{ __('emails.customer_order_confirmation.at') }} {{ business_time($order->created_at, $order->branch)?->format('H:i') }}</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Fulfillment method --}}
                    <tr>
                        <td style="background-color:#ffffff;padding:0 36px 24px;border-left:1px solid #d1fae5;border-right:1px solid #d1fae5;">
                            <p style="margin:0 0 10px;font-size:13px;font-weight:bold;color:#064E3B;text-transform:uppercase;letter-spacing:0.5px;border-bottom:2px solid #d1fae5;padding-bottom:8px;">
                                {{ $order->fulfillment_method === 'pickup'
                                    ? __('emails.customer_order_confirmation.pickup')
                                    : __('emails.customer_order_confirmation.delivery')
                                }}
                            </p>
                            <table width="100%" cellpadding="0" cellspacing="0">
                                @if($order->fulfillment_method === 'pickup' && $order->branch)
                                <tr>
                                    <td style="padding:3px 0;font-size:13px;color:#64748b;width:110px;">{{ __('emails.customer_order_confirmation.pickup_location') }}</td>
                                    <td style="padding:3px 0;font-size:13px;color:#1e293b;font-weight:600;">{{ $order->branch->name }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:3px 0;font-size:13px;color:#64748b;">{{ __('emails.customer_order_confirmation.address') }}</td>
                                    <td style="padding:3px 0;font-size:13px;color:#1e293b;">{{ $order->branch->address }}</td>
                                </tr>
                                @endif
                                @if($order->fulfillment_method === 'delivery' && $order->delivery_address)
                                <tr>
                                    <td style="padding:3px 0;font-size:13px;color:#64748b;width:110px;">{{ __('emails.customer_order_confirmation.delivery_address') }}</td>
                                    <td style="padding:3px 0;font-size:13px;color:#1e293b;font-weight:600;">{{ $order->delivery_address }}</td>
                                </tr>
                                @endif
                                @if($order->delivery_distance_km)
                                <tr>
                                    <td style="padding:3px 0;font-size:13px;color:#64748b;">{{ __('emails.customer_order_confirmation.distance') }}</td>
                                    <td style="padding:3px 0;font-size:13px;color:#1e293b;">{{ number_format((float) $order->delivery_distance_km, 1, ',', '.') }} km</td>
                                </tr>
                                @endif
                                @if($order->requested_date)
                                <tr>
                                    <td style="padding:3px 0;font-size:13px;color:#64748b;">{{ $order->fulfillment_method === 'pickup' ? __('emails.customer_order_confirmation.eta_pickup') : __('emails.customer_order_confirmation.eta_delivery') }}</td>
                                    <td style="padding:3px 0;font-size:13px;color:#1e293b;">
                                        {{ \Carbon\Carbon::parse($order->requested_date)->format('d/m/Y') }}
                                        @if($order->requested_time) {{ __('emails.customer_order_confirmation.at') }} {{ substr($order->requested_time, 0, 5) }}@endif
                                    </td>
                                </tr>
                                @endif
                                @if($order->note)
                                <tr>
                                    <td style="padding:3px 0;font-size:13px;color:#64748b;">{{ __('emails.customer_order_confirmation.note') }}</td>
                                    <td style="padding:3px 0;font-size:13px;color:#1e293b;font-style:italic;">{{ $order->note }}</td>
                                </tr>
                                @endif
                            </table>
                        </td>
                    </tr>

                    {{-- Items --}}
                    <tr>
                        <td style="background-color:#ffffff;padding:0 36px 24px;border-left:1px solid #d1fae5;border-right:1px solid #d1fae5;">
                            <p style="margin:0 0 12px;font-size:13px;font-weight:bold;color:#064E3B;text-transform:uppercase;letter-spacing:0.5px;border-bottom:2px solid #d1fae5;padding-bottom:8px;">{{ __('emails.customer_order_confirmation.items') }}</p>
                            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                                <thead>
                                    <tr style="background-color:#f0fdf4;">
                                        <th style="padding:9px 12px;text-align:left;font-size:11px;color:#64748b;border-bottom:1px solid #d1fae5;letter-spacing:0.5px;text-transform:uppercase;" align="left">{{ __('emails.customer_order_confirmation.dish') }}</th>
                                        <th style="padding:9px 12px;text-align:center;font-size:11px;color:#64748b;border-bottom:1px solid #d1fae5;letter-spacing:0.5px;text-transform:uppercase;" align="center">{{ __('emails.customer_order_confirmation.qty') }}</th>
                                        <th style="padding:9px 12px;text-align:right;font-size:11px;color:#64748b;border-bottom:1px solid #d1fae5;letter-spacing:0.5px;text-transform:uppercase;" align="right">{{ __('emails.customer_order_confirmation.unit_price') }}</th>
                                        <th style="padding:9px 12px;text-align:right;font-size:11px;color:#64748b;border-bottom:1px solid #d1fae5;letter-spacing:0.5px;text-transform:uppercase;" align="right">{{ __('emails.customer_order_confirmation.line_total') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($order->items as $item)
                                    <tr>
                                        <td style="padding:10px 12px;font-size:13px;color:#1e293b;border-bottom:1px solid #f1f5f9;vertical-align:top;">
                                            <strong>{{ $item->quantity }}x {{ $item->dish_name }}</strong>
                                            @if(is_array($item->options_snapshot))
                                                @foreach($item->options_snapshot as $groupName => $options)
                                                    @if(is_array($options))
                                                        @foreach($options as $option)
                                                            @if(is_array($option))
                                                                <br><span style="color:#64748b;font-size:12px;">+ {{ $groupName }}: {{ $option['name'] ?? '' }}</span>
                                                            @endif
                                                        @endforeach
                                                    @endif
                                                @endforeach
                                            @endif
                                            @if($item->customization_note)
                                                <br><span style="color:#059669;font-size:12px;font-style:italic;">{{ $item->customization_note }}</span>
                                            @endif
                                        </td>
                                        <td style="padding:10px 12px;font-size:13px;color:#1e293b;text-align:center;border-bottom:1px solid #f1f5f9;" align="center">{{ $item->quantity }}</td>
                                        <td style="padding:10px 12px;font-size:13px;color:#1e293b;text-align:right;border-bottom:1px solid #f1f5f9;" align="right">{{ format_money($item->unit_price) }}</td>
                                        <td style="padding:10px 12px;font-size:13px;color:#1e293b;font-weight:600;text-align:right;border-bottom:1px solid #f1f5f9;" align="right">{{ format_money($item->line_total) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </td>
                    </tr>

                    {{-- Totals --}}
                    <tr>
                        <td style="background-color:#ffffff;padding:0 36px 24px;border-left:1px solid #d1fae5;border-right:1px solid #d1fae5;">
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-left:auto;">
                                <tr>
                                    <td style="padding:4px 0;font-size:13px;color:#64748b;">{{ __('emails.customer_order_confirmation.totals_subtotal') }}</td>
                                    <td style="padding:4px 0;font-size:13px;color:#1e293b;text-align:right;" align="right">{{ format_money($order->subtotal) }}</td>
                                </tr>
                                @if($order->shipping_fee > 0)
                                <tr>
                                    <td style="padding:4px 0;font-size:13px;color:#64748b;">{{ __('emails.customer_order_confirmation.totals_shipping') }}</td>
                                    <td style="padding:4px 0;font-size:13px;color:#1e293b;text-align:right;" align="right">{{ format_money($order->shipping_fee) }}</td>
                                </tr>
                                @endif
                                @if($order->discount_total > 0)
                                <tr>
                                    <td style="padding:4px 0;font-size:13px;color:#059669;">{{ __('emails.customer_order_confirmation.totals_discount') }}{{ $order->voucher_code ? ' ('.$order->voucher_code.')' : '' }}</td>
                                    <td style="padding:4px 0;font-size:13px;color:#059669;text-align:right;" align="right">-{{ format_money($order->discount_total) }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <td style="padding:12px 0 4px;font-size:18px;font-weight:bold;color:#064E3B;border-top:2px solid #d1fae5;" colspan="2" align="right">
                                        {{ __('emails.customer_order_confirmation.totals_total') }}: {{ format_money($order->total) }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Payment status --}}
                    <tr>
                        <td style="background-color:#ffffff;padding:0 36px 28px;border-left:1px solid #d1fae5;border-right:1px solid #d1fae5;">
                            <p style="margin:0 0 10px;font-size:13px;font-weight:bold;color:#064E3B;text-transform:uppercase;letter-spacing:0.5px;border-bottom:2px solid #d1fae5;padding-bottom:8px;">{{ __('emails.customer_order_confirmation.payment') }}</p>
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="padding:3px 0;font-size:13px;color:#64748b;width:130px;">{{ __('emails.customer_order_confirmation.payment_method') }}</td>
                                    <td style="padding:3px 0;font-size:13px;color:#1e293b;font-weight:600;">
                                        @if($order->payment_method === 'viva')
                                            {{ __('emails.customer_order_confirmation.payment_method_viva') }}
                                        @else
                                            {{ __('emails.customer_order_confirmation.payment_method_offline') }}
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:3px 0;font-size:13px;color:#64748b;">{{ __('emails.customer_order_confirmation.payment_status') }}</td>
                                    <td style="padding:3px 0;font-size:13px;font-weight:700;">
                                        @if($order->payment_status === 'paid')
                                            <span style="color:#059669;">{{ __('emails.customer_order_confirmation.payment_paid') }}</span>
                                        @else
                                            <span style="color:#b45309;">{{ __('emails.customer_order_confirmation.payment_unpaid') }}</span>
                                            @if($order->payment_method === 'viva')
                                                — {{ __('emails.customer_order_confirmation.payment_viva_unpaid_hint') }}
                                            @else
                                                — {{ __('emails.customer_order_confirmation.payment_offline_unpaid_hint') }}
                                            @endif
                                        @endif
                                    </td>
                                </tr>
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
    {{ __('emails.customer_order_confirmation.track_note') }}
</p>
<p style="margin:0 0 18px;">
    <a href="{{ $trackUrl }}" style="display:inline-block;background-color:#059669;color:#ffffff;text-decoration:none;font-size:13px;font-weight:800;padding:12px 22px;border-radius:10px;text-transform:uppercase;letter-spacing:0.6px;">
        {{ __('emails.customer_order_confirmation.track_cta') }}
    </a>
</p>

<p style="margin:0 0 12px;font-size:13px;color:#64748b;line-height:1.6;">
                                {{ __('emails.customer_order_confirmation.support') }}<br>
                                <a href="tel:+30XXXXXXXXX" style="color:#059669;text-decoration:none;font-weight:bold;">{{ $order->branch?->phone ?? setting('contact_phone', '') }}</a>
                            </p>
                            <p style="margin:0;font-size:12px;color:#94a3b8;">
                                {{ __('emails.customer_order_confirmation.team') }}
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
@php app()->setLocale($prevLocale); @endphp
</html>
