<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt bàn mới</title>
</head>
<body style="margin:0;padding:0;background:#eef4ec;font-family:Segoe UI,Arial,sans-serif;color:#172018;">
    @php
        $branch = $reservation->branch;
        $date = \Carbon\Carbon::parse($reservation->reservation_date)->format('d/m/Y');
        $adminUrl = route('admin.reservations.show', $reservation);
    @endphp
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#eef4ec;padding:28px 14px;">
        <tr>
            <td align="center">
                <table width="640" cellpadding="0" cellspacing="0" style="width:100%;max-width:640px;background:#ffffff;border-radius:18px;overflow:hidden;border:1px solid #dce8d7;">
                    <tr>
                        <td style="background:#064e3b;color:#ffffff;padding:28px 36px;">
                            <p style="margin:0;font-size:24px;font-weight:800;">Đặt bàn mới</p>
                            <p style="margin:8px 0 0;font-size:14px;color:#bbf7d0;">{{ $branch?->name ?: 'Paprika' }} - {{ $date }} {{ $reservation->reservation_time }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px 36px;">
                            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                                <tr><td style="padding:9px 0;color:#64748b;width:150px;">Khách</td><td style="padding:9px 0;font-weight:700;">{{ $reservation->name }}</td></tr>
                                <tr><td style="padding:9px 0;color:#64748b;">Điện thoại</td><td style="padding:9px 0;"><a href="tel:{{ $reservation->phone }}" style="color:#059669;text-decoration:none;font-weight:700;">{{ $reservation->phone }}</a></td></tr>
                                @if($reservation->email)
                                    <tr><td style="padding:9px 0;color:#64748b;">Email</td><td style="padding:9px 0;"><a href="mailto:{{ $reservation->email }}" style="color:#059669;text-decoration:none;">{{ $reservation->email }}</a></td></tr>
                                @endif
                                <tr><td style="padding:9px 0;color:#64748b;">Cơ sở</td><td style="padding:9px 0;font-weight:700;">{{ $branch?->name ?: 'Chưa chọn' }}</td></tr>
                                <tr><td style="padding:9px 0;color:#64748b;">Thời gian</td><td style="padding:9px 0;font-weight:700;">{{ $date }} lúc {{ $reservation->reservation_time }}</td></tr>
                                <tr><td style="padding:9px 0;color:#64748b;">Số khách</td><td style="padding:9px 0;font-weight:700;">{{ $reservation->guests }} khách</td></tr>
                                @if($reservation->table)
                                    <tr><td style="padding:9px 0;color:#64748b;">Bàn</td><td style="padding:9px 0;">{{ $reservation->tableLabel() }}</td></tr>
                                @endif
                                @if($reservation->note)
                                    <tr><td style="padding:9px 0;color:#64748b;vertical-align:top;">Ghi chú khách</td><td style="padding:9px 0;color:#92400e;">{{ $reservation->note }}</td></tr>
                                @endif
                            </table>
                            <p style="margin:24px 0 0;text-align:center;">
                                <a href="{{ $adminUrl }}" style="display:inline-block;background:#059669;color:#ffffff;text-decoration:none;font-weight:800;padding:13px 26px;border-radius:10px;">Mở chi tiết đặt bàn</a>
                            </p>
                            <p style="margin:14px 0 0;text-align:center;color:#94a3b8;font-size:12px;">{{ $adminUrl }}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
