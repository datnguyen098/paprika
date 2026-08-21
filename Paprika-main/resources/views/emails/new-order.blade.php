<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Order #{{ $order->code }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f0fdf4;font-family:Segoe UI,Helvetica Neue,Helvetica,Arial,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f0fdf4;padding:32px 16px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">

                    {{-- Header --}}
                    <tr>
                        <td style="background-color:#064E3B;padding:32px 40px;border-radius:16px 16px 0 0;">
                            <p style="margin:0;font-size:28px;font-weight:800;color:#ffffff;letter-spacing:-0.5px;">Paprika</p>
                            <p style="margin:6px 0 0;font-size:14px;color:#6ee7b7;">You have a new order</p>
                        </td>
                    </tr>

                    {{-- Order code + time + branch --}}
                    <tr>
                        <td style="background-color:#ffffff;padding:28px 40px;border-left:1px solid #d1fae5;border-right:1px solid #d1fae5;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>
                                        <p style="margin:0;font-size:11px;color:#94a3b8;text-transform:uppercase;letter-spacing:1.5px;font-weight:600;">Order ID</p>
                                        <p style="margin:6px 0 0;font-size:26px;font-weight:800;color:#064E3B;letter-spacing:-0.5px;">#{{ $order->code }}</p>
                                    </td>
                                    <td align="right">
                                        <p style="margin:0;font-size:11px;color:#94a3b8;text-transform:uppercase;letter-spacing:1.5px;font-weight:600;">Received</p>
                                        <p style="margin:6px 0 0;font-size:14px;color:#334155;font-weight:600;">{{ business_time($order->created_at, $order->branch)?->format('M d, Y') }} at {{ business_time($order->created_at, $order->branch)?->format('H:i') }}</p>
                                    </td>
                                </tr>
                            </table>
                            @if($order->branch)
                            <div style="margin-top:16px;display:inline-flex;align-items:center;gap:6px;background:#f0fdf4;border:1px solid #d1fae5;border-radius:8px;padding:6px 12px;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                                <span style="font-size:13px;font-weight:600;color:#065f46;">{{ $order->branch->name }}</span>
                            </div>
                            @endif
                        </td>
                    </tr>

                    {{-- Customer info --}}
                    <tr>
                        <td style="background-color:#ffffff;padding:0 40px 28px;border-left:1px solid #d1fae5;border-right:1px solid #d1fae5;">
                            <p style="margin:24px 0 14px;font-size:13px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:1.5px;border-bottom:2px solid #f1f5f9;padding-bottom:10px;">Customer Information</p>
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="padding:6px 0;font-size:12px;color:#94a3b8;width:120px;">Name</td>
                                    <td style="padding:6px 0;font-size:14px;color:#1e293b;font-weight:700;">{{ $order->customer_name }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0;font-size:12px;color:#94a3b8;">Phone</td>
                                    <td style="padding:6px 0;font-size:14px;color:#1e293b;"><a href="tel:{{ $order->customer_phone }}" style="color:#059669;text-decoration:none;font-weight:600;">{{ $order->customer_phone }}</a></td>
                                </tr>
                                @if($order->customer_email)
                                <tr>
                                    <td style="padding:6px 0;font-size:12px;color:#94a3b8;">Email</td>
                                    <td style="padding:6px 0;font-size:14px;color:#1e293b;"><a href="mailto:{{ $order->customer_email }}" style="color:#059669;text-decoration:none;font-weight:600;">{{ $order->customer_email }}</a></td>
                                </tr>
                                @endif
                                <tr>
                                    <td style="padding:6px 0;font-size:12px;color:#94a3b8;">Method</td>
                                    <td style="padding:6px 0;font-size:14px;color:#1e293b;font-weight:700;">{{ $order->fulfillmentLabel() }}</td>
                                </tr>
                                @if($order->fulfillment_method === 'pickup' && $order->branch)
                                <tr>
                                    <td style="padding:6px 0;font-size:12px;color:#94a3b8;">Pickup at</td>
                                    <td style="padding:6px 0;font-size:14px;color:#475569;">{{ $order->branch->name }} — {{ $order->branch->address }}</td>
                                </tr>
                                @endif
                                @if($order->delivery_address)
                                <tr>
                                    <td style="padding:6px 0;font-size:12px;color:#94a3b8;">Delivery to</td>
                                    <td style="padding:6px 0;font-size:14px;color:#475569;">{{ $order->delivery_address }}</td>
                                </tr>
                                @endif
                                @if($order->requested_date)
                                <tr>
                                    <td style="padding:6px 0;font-size:12px;color:#94a3b8;">Requested</td>
                                    <td style="padding:6px 0;font-size:14px;color:#475569;">
                                        {{ \Carbon\Carbon::parse($order->requested_date)->format('M d, Y') }}
                                        @if($order->requested_time) at {{ $order->requested_time }}@endif
                                    </td>
                                </tr>
                                @endif
                                @if($order->note)
                                <tr>
                                    <td style="padding:6px 0;font-size:12px;color:#94a3b8;">Note</td>
                                    <td style="padding:6px 0;font-size:14px;color:#b45309;font-style:italic;background:#fffbeb;padding:6px 10px;border-radius:6px;border:1px solid #fde68a;">{{ $order->note }}</td>
                                </tr>
                                @endif
                            </table>
                        </td>
                    </tr>

                    {{-- Items --}}
                    <tr>
                        <td style="background-color:#ffffff;padding:0 40px 28px;border-left:1px solid #d1fae5;border-right:1px solid #d1fae5;">
                            <p style="margin:0 0 14px;font-size:13px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:1.5px;border-bottom:2px solid #f1f5f9;padding-bottom:10px;">Order Items</p>
                            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                                <thead>
                                    <tr style="background-color:#f8fafc;">
                                        <th style="padding:10px 14px;text-align:left;font-size:11px;color:#94a3b8;text-transform:uppercase;letter-spacing:1px;border-bottom:2px solid #e2e8f0;" align="left">Item</th>
                                        <th style="padding:10px 14px;text-align:center;font-size:11px;color:#94a3b8;text-transform:uppercase;letter-spacing:1px;border-bottom:2px solid #e2e8f0;" align="center">Qty</th>
                                        <th style="padding:10px 14px;text-align:right;font-size:11px;color:#94a3b8;text-transform:uppercase;letter-spacing:1px;border-bottom:2px solid #e2e8f0;" align="right">Price</th>
                                        <th style="padding:10px 14px;text-align:right;font-size:11px;color:#94a3b8;text-transform:uppercase;letter-spacing:1px;border-bottom:2px solid #e2e8f0;" align="right">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($order->items as $item)
                                    <tr>
                                        <td style="padding:12px 14px;font-size:14px;color:#1e293b;border-bottom:1px solid #f1f5f9;vertical-align:top;">
                                            <strong>{{ $item->dish_name }}</strong>
                                            @if(is_array($item->options_snapshot))
                                                @foreach($item->options_snapshot as $option)
                                                    @if(is_array($option))
                                                        <br><span style="color:#94a3b8;font-size:12px;">+ {{ $option['group_name'] ?? '' }}: {{ $option['name'] ?? $option['value'] ?? '' }}</span>
                                                    @endif
                                                @endforeach
                                            @endif
                                            @if($item->customization_note)
                                                <br><span style="color:#94a3b8;font-size:12px;font-style:italic;">Note: {{ $item->customization_note }}</span>
                                            @endif
                                        </td>
                                        <td style="padding:12px 14px;font-size:14px;color:#475569;text-align:center;border-bottom:1px solid #f1f5f9;" align="center">{{ $item->quantity }}</td>
                                        <td style="padding:12px 14px;font-size:14px;color:#475569;text-align:right;border-bottom:1px solid #f1f5f9;" align="right">{{ format_money($item->unit_price) }}</td>
                                        <td style="padding:12px 14px;font-size:14px;color:#1e293b;font-weight:700;text-align:right;border-bottom:1px solid #f1f5f9;" align="right">{{ format_money($item->line_total) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </td>
                    </tr>

                    {{-- Totals --}}
                    <tr>
                        <td style="background-color:#ffffff;padding:0 40px 28px;border-left:1px solid #d1fae5;border-right:1px solid #d1fae5;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="padding:6px 0;font-size:14px;color:#64748b;">Subtotal</td>
                                    <td style="padding:6px 0;font-size:14px;color:#475569;text-align:right;" align="right">{{ format_money($order->subtotal) }}</td>
                                </tr>
                                @if($order->shipping_fee > 0)
                                <tr>
                                    <td style="padding:6px 0;font-size:14px;color:#64748b;">Delivery Fee</td>
                                    <td style="padding:6px 0;font-size:14px;color:#475569;text-align:right;" align="right">{{ format_money($order->shipping_fee) }}</td>
                                </tr>
                                @endif
                                @if($order->discount_total > 0)
                                <tr>
                                    <td style="padding:6px 0;font-size:14px;color:#64748b;">Discount{{ $order->voucher_code ? ' ('.$order->voucher_code.')' : '' }}</td>
                                    <td style="padding:6px 0;font-size:14px;color:#059669;text-align:right;font-weight:700;" align="right">-{{ format_money($order->discount_total) }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <td style="padding:16px 0 4px;font-size:18px;font-weight:800;color:#064E3B;border-top:2px solid #d1fae5;" colspan="2" align="right">Total: {{ format_money($order->total) }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Payment --}}
                    <tr>
                        <td style="background-color:#ffffff;padding:0 40px 28px;border-left:1px solid #d1fae5;border-right:1px solid #d1fae5;">
                            <p style="margin:0 0 14px;font-size:13px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:1.5px;border-bottom:2px solid #f1f5f9;padding-bottom:10px;">Payment</p>
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="padding:6px 0;font-size:14px;color:#64748b;width:160px;">Method</td>
                                    <td style="padding:6px 0;font-size:14px;color:#1e293b;font-weight:700;">
                                        @if($order->payment_method === 'viva')
                                            Card / Viva Wallet
                                        @else
                                            Cash / On-site
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0;font-size:14px;color:#64748b;">Status</td>
                                    <td style="padding:6px 0;font-size:14px;font-weight:700;">
                                        @if($order->payment_status === 'paid')
                                            <span style="color:#059669;">Paid</span>
                                        @else
                                            <span style="color:#d97706;">Pending</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- CTA --}}
                    <tr>
                        <td style="background-color:#f0fdf4;padding:28px 40px;border:1px solid #d1fae5;border-radius:0 0 16px 16px;" align="center">
                            <a href="{{ url('/admin/orders/' . $order->id) }}"
                               style="display:inline-block;background-color:#059669;color:#ffffff;text-decoration:none;font-size:15px;font-weight:700;padding:14px 36px;border-radius:10px;box-shadow:0 4px 12px rgba(5,150,105,0.3);">
                                View Order Details
                            </a>
                            <p style="margin:16px 0 0;font-size:12px;color:#94a3b8;">
                                {{ url('/admin/orders/' . $order->id) }}
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
