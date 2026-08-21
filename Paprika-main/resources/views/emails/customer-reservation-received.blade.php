<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đã nhận yêu cầu đặt bàn</title>
</head>
<body style="margin:0;padding:0;background:#f7f5ef;font-family:Segoe UI,Arial,sans-serif;color:#1f2933;">
    @php
        $branch = $reservation->branch;
        $restaurantName = localized_setting('restaurant_name', config('app.name', 'Paprika'));
        $date = \Carbon\Carbon::parse($reservation->reservation_date)->format('d/m/Y');
        $phone = $branch?->phone ?: $branch?->hotline ?: setting('restaurant_phone', '');
    @endphp
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f7f5ef;padding:28px 14px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="width:100%;max-width:600px;background:#ffffff;border-radius:18px;overflow:hidden;border:1px solid #e8dfc6;">
                    <tr>
                        <td style="background:#667132;color:#ffffff;padding:26px 34px;">
                            <p style="margin:0;font-size:26px;font-weight:800;">{{ $restaurantName }}</p>
                            <p style="margin:6px 0 0;font-size:15px;">Đã nhận yêu cầu đặt bàn của bạn</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:30px 34px;">
                            <p style="margin:0 0 18px;font-size:16px;line-height:1.6;">Cảm ơn {{ $reservation->name }}. Nhà hàng đã nhận yêu cầu đặt bàn và sẽ liên hệ xác nhận sớm nhất.</p>
                            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                                <tr>
                                    <td style="padding:10px 0;color:#667085;width:145px;">Cơ sở</td>
                                    <td style="padding:10px 0;font-weight:700;">{{ $branch?->name ?: 'Paprika' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 0;color:#667085;">Thời gian</td>
                                    <td style="padding:10px 0;font-weight:700;">{{ $date }} lúc {{ $reservation->reservation_time }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 0;color:#667085;">Số khách</td>
                                    <td style="padding:10px 0;font-weight:700;">{{ $reservation->guests }} khách</td>
                                </tr>
                                @if($branch?->address)
                                    <tr>
                                        <td style="padding:10px 0;color:#667085;">Địa chỉ</td>
                                        <td style="padding:10px 0;">{{ $branch->address }}</td>
                                    </tr>
                                @endif
                                @if($phone)
                                    <tr>
                                        <td style="padding:10px 0;color:#667085;">Liên hệ</td>
                                        <td style="padding:10px 0;"><a href="tel:{{ $phone }}" style="color:#667132;text-decoration:none;font-weight:700;">{{ $phone }}</a></td>
                                    </tr>
                                @endif
                                @if($reservation->note)
                                    <tr>
                                        <td style="padding:10px 0;color:#667085;">Ghi chú</td>
                                        <td style="padding:10px 0;">{{ $reservation->note }}</td>
                                    </tr>
                                @endif
                            </table>
                            <p style="margin:22px 0 0;padding:14px 16px;border-radius:12px;background:#fff7df;border:1px solid #efd48a;color:#7a4a08;font-weight:700;line-height:1.5;">
                                Bạn chỉ được giữ bàn sau khi nhà hàng liên hệ xác nhận.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
