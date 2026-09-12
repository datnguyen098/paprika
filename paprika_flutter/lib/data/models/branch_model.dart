import 'package:equatable/equatable.dart';

/// Branch (chi nhánh nhà hàng).
///
/// Mock controller hiện trả 11 field. Model Branch thật (DB) có 30+ field
/// (hotline, slug, timezone, open_days, delivery info, google_map_iframe...).
/// Ở đây mình define TẤT CẢ optional field mà BE có thể trả — factory dùng
/// `as String?`/null fallback, nên khi BE đổi mock → DB, parser không vỡ.
///
/// Mapping field xem Paprika-main/app/Models/Branch.php.
class Branch extends Equatable {
  const Branch({
    required this.id,
    required this.name,
    required this.address,
    required this.phone,
    this.email,
    this.hotline,
    this.openingHours,
    this.latitude,
    this.longitude,
    this.image,
    this.description,
    this.isActive = true,
    // Optional - khi BE mock → DB sẽ có
    this.slug,
    this.timezone,
    this.openDays,
    this.acceptsOnlineOrders,
    this.acceptsPickupOrders,
    this.acceptsDeliveryOrders,
    this.deliveryMinOrderAmount,
    this.deliveryFreeOrderAmount,
    this.deliveryMaxDistanceKm,
    this.deliveryNote,
    this.googleMapIframe,
    this.metaTitle,
    this.metaDescription,
  });

  final int id;
  final String name;
  final String address;
  final String phone;
  final String? email;
  final String? hotline;
  final String? openingHours;
  final double? latitude;
  final double? longitude;
  final String? image;
  final String? description;
  final bool isActive;

  // DB fields (optional)
  final String? slug;
  final String? timezone;
  final String? openDays;
  final bool? acceptsOnlineOrders;
  final bool? acceptsPickupOrders;
  final bool? acceptsDeliveryOrders;
  final int? deliveryMinOrderAmount;
  final int? deliveryFreeOrderAmount;
  final double? deliveryMaxDistanceKm;
  final String? deliveryNote;
  final String? googleMapIframe;
  final String? metaTitle;
  final String? metaDescription;

  /// Parse `google_map_iframe` (HTML) → src URL dùng cho WebView/launch.
  /// Trả null nếu không có iframe hoặc không tìm thấy src.
  String? get mapEmbedSrc {
    final iframe = googleMapIframe;
    if (iframe == null || iframe.isEmpty) return null;
    // Match src="..." hoặc src='...' trong HTML iframe tag.
    final match = RegExp('src=["\']([^"\']+)["\']').firstMatch(iframe);
    return match?.group(1);
  }

  /// Hotline fallback về phone nếu không có hotline riêng.
  String get displayHotline =>
      (hotline != null && hotline!.isNotEmpty) ? hotline! : phone;

  /// Open days hiển thị dạng ngắn (T2-CN) — parse từ "1,2,3,4,5,6,0"
  /// trong đó PHP lưu theo Carbon (1=T2...0=CN).
  String get openDaysShort {
    final raw = openDays;
    if (raw == null || raw.isEmpty) return '';
    const labels = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'];
    final days = raw
        .split(',')
        .map((s) => s.trim())
        .where((s) => s.isNotEmpty)
        .map(int.tryParse)
        .whereType<int>()
        .toList();
    if (days.length == 7) return 'T2 - CN';
    return days.map((d) => labels[d.clamp(0, 6)]).join(' · ');
  }

  factory Branch.fromJson(Map<String, dynamic> json) {
    double? parseDouble(Object? raw) {
      if (raw == null) return null;
      if (raw is num) return raw.toDouble();
      if (raw is String) return double.tryParse(raw);
      return null;
    }

    return Branch(
      id: (json['id'] as num).toInt(),
      name: json['name'] as String? ?? '',
      address: json['address'] as String? ?? '',
      phone: json['phone'] as String? ?? '',
      email: json['email'] as String?,
      hotline: json['hotline'] as String?,
      openingHours: json['opening_hours'] as String?,
      latitude: parseDouble(json['latitude']),
      longitude: parseDouble(json['longitude']),
      image: json['image'] as String?,
      description: json['description'] as String?,
      isActive: json['is_active'] as bool? ?? true,
      slug: json['slug'] as String?,
      timezone: json['timezone'] as String?,
      openDays: json['open_days'] as String?,
      acceptsOnlineOrders: json['accepts_online_orders'] as bool?,
      acceptsPickupOrders: json['accepts_pickup_orders'] as bool?,
      acceptsDeliveryOrders: json['accepts_delivery_orders'] as bool?,
      deliveryMinOrderAmount:
          json['delivery_min_order_amount'] != null
              ? (json['delivery_min_order_amount'] as num).toInt()
              : null,
      deliveryFreeOrderAmount:
          json['delivery_free_order_amount'] != null
              ? (json['delivery_free_order_amount'] as num).toInt()
              : null,
      deliveryMaxDistanceKm: parseDouble(json['delivery_max_distance_km']),
      deliveryNote: json['delivery_note'] as String?,
      googleMapIframe: json['google_map_iframe'] as String?,
      metaTitle: json['meta_title'] as String?,
      metaDescription: json['meta_description'] as String?,
    );
  }

  @override
  List<Object?> get props => [id, name, slug];
}
