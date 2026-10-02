import 'cart_model.dart';

class AddressSuggestion {
  const AddressSuggestion({
    required this.formatted,
    this.placeId,
    this.latitude,
    this.longitude,
  });

  final String formatted;
  final String? placeId;
  final double? latitude;
  final double? longitude;

  factory AddressSuggestion.fromJson(Map<String, dynamic> json) {
    double? parseDouble(Object? raw) {
      if (raw == null) return null;
      if (raw is num) return raw.toDouble();
      if (raw is String) return double.tryParse(raw);
      return null;
    }

    return AddressSuggestion(
      formatted: json['formatted'] as String? ?? '',
      placeId: json['place_id'] as String?,
      latitude: parseDouble(json['latitude']),
      longitude: parseDouble(json['longitude']),
    );
  }
}

class AddressReverseResponse {
  const AddressReverseResponse({
    this.formattedAddress,
    this.placeId,
    this.latitude,
    this.longitude,
  });

  final String? formattedAddress;
  final String? placeId;
  final double? latitude;
  final double? longitude;

  factory AddressReverseResponse.fromJson(Map<String, dynamic> json) {
    double? parseDouble(Object? raw) {
      if (raw == null) return null;
      if (raw is num) return raw.toDouble();
      if (raw is String) return double.tryParse(raw);
      return null;
    }

    return AddressReverseResponse(
      formattedAddress: json['formatted_address'] as String?,
      placeId: json['place_id'] as String?,
      latitude: parseDouble(json['latitude']),
      longitude: parseDouble(json['longitude']),
    );
  }
}

class CreateOrderRequest {
  const CreateOrderRequest({
    required this.branchId,
    required this.customerName,
    required this.customerPhone,
    required this.fulfillmentMethod,
    required this.items,
    this.customerEmail,
    this.deliveryAddress,
    this.deliveryLatitude,
    this.deliveryLongitude,
    this.deliveryPlaceId,
    this.requestedDate,
    this.requestedTime,
    this.voucherCode,
    this.note,
  });

  final int branchId;
  final String customerName;
  final String customerPhone;
  final String? customerEmail;
  final String fulfillmentMethod;
  final String? deliveryAddress;
  final double? deliveryLatitude;
  final double? deliveryLongitude;
  final String? deliveryPlaceId;
  final String? requestedDate;
  final String? requestedTime;
  final String? voucherCode;
  final String? note;
  final List<CartItem> items;

  Map<String, dynamic> toJson() => {
        'branch_id': branchId,
        'customer_name': customerName.trim(),
        'customer_phone': customerPhone.trim(),
        'customer_email':
            customerEmail == null || customerEmail!.trim().isEmpty
                ? null
                : customerEmail!.trim(),
        'fulfillment_method': fulfillmentMethod,
        'delivery_address':
            deliveryAddress == null || deliveryAddress!.trim().isEmpty
                ? null
                : deliveryAddress!.trim(),
        'delivery_latitude': deliveryLatitude,
        'delivery_longitude': deliveryLongitude,
        'delivery_place_id':
            deliveryPlaceId == null || deliveryPlaceId!.trim().isEmpty
                ? null
                : deliveryPlaceId!.trim(),
        'requested_date': requestedDate,
        'requested_time': requestedTime,
        'voucher_code':
            voucherCode == null || voucherCode!.trim().isEmpty
                ? null
                : voucherCode!.trim(),
        'note': note == null || note!.trim().isEmpty ? null : note!.trim(),
        'items': items.map((item) => item.toOrderJson()).toList(),
      };
}

class DeliveryQuoteRequest {
  const DeliveryQuoteRequest({
    required this.branchId,
    required this.fulfillmentMethod,
    required this.subtotal,
    this.deliveryAddress,
    this.deliveryLatitude,
    this.deliveryLongitude,
    this.deliveryPlaceId,
  });

  final int branchId;
  final String fulfillmentMethod;
  final int subtotal;
  final String? deliveryAddress;
  final double? deliveryLatitude;
  final double? deliveryLongitude;
  final String? deliveryPlaceId;

  Map<String, dynamic> toJson() => {
        'branch_id': branchId,
        'fulfillment_method': fulfillmentMethod,
        'subtotal': subtotal,
        'delivery_address':
            deliveryAddress == null || deliveryAddress!.trim().isEmpty
                ? null
                : deliveryAddress!.trim(),
        'delivery_latitude': deliveryLatitude,
        'delivery_longitude': deliveryLongitude,
        'delivery_place_id':
            deliveryPlaceId == null || deliveryPlaceId!.trim().isEmpty
                ? null
                : deliveryPlaceId!.trim(),
      };
}

class DeliveryQuoteResponse {
  const DeliveryQuoteResponse({
    required this.available,
    required this.manual,
    required this.fee,
    required this.total,
    this.source,
    this.message,
    this.feeFormatted,
    this.distanceKm,
    this.distanceLabel,
    this.zoneLabel,
    this.latitude,
    this.longitude,
    this.placeId,
    this.formattedAddress,
  });

  final bool available;
  final bool manual;
  final String? source;
  final String? message;
  final int fee;
  final String? feeFormatted;
  final double? distanceKm;
  final String? distanceLabel;
  final String? zoneLabel;
  final int total;
  final double? latitude;
  final double? longitude;
  final String? placeId;
  final String? formattedAddress;

  factory DeliveryQuoteResponse.fromJson(Map<String, dynamic> json) {
    double? parseDouble(Object? raw) {
      if (raw == null) return null;
      if (raw is num) return raw.toDouble();
      if (raw is String) return double.tryParse(raw);
      return null;
    }

    return DeliveryQuoteResponse(
      available: json['available'] as bool? ?? false,
      manual: json['manual'] as bool? ?? false,
      source: json['source'] as String?,
      message: json['message'] as String?,
      fee: (json['fee'] as num?)?.toInt() ?? 0,
      feeFormatted: json['fee_formatted'] as String?,
      distanceKm: parseDouble(json['distance_km']),
      distanceLabel: json['distance_label'] as String?,
      zoneLabel: json['zone_label'] as String?,
      total: (json['total'] as num?)?.toInt() ?? 0,
      latitude: parseDouble(json['latitude']),
      longitude: parseDouble(json['longitude']),
      placeId: json['place_id'] as String?,
      formattedAddress: json['formatted_address'] as String?,
    );
  }
}

class OrderAvailabilityRequest {
  const OrderAvailabilityRequest({
    required this.branchId,
    required this.items,
    this.requestedDate,
    this.requestedTime,
  });

  final int branchId;
  final String? requestedDate;
  final String? requestedTime;
  final List<CartItem> items;

  Map<String, dynamic> toJson() => {
        'branch_id': branchId,
        'requested_date':
            requestedDate == null || requestedDate!.trim().isEmpty
                ? null
                : requestedDate!.trim(),
        'requested_time':
            requestedTime == null || requestedTime!.trim().isEmpty
                ? null
                : requestedTime!.trim(),
        'items': items.map((item) => item.toOrderJson()).toList(),
      };
}

class UnavailableOrderItem {
  const UnavailableOrderItem({
    required this.name,
    required this.windows,
    required this.label,
  });

  final String name;
  final List<String> windows;
  final String label;

  factory UnavailableOrderItem.fromJson(Map<String, dynamic> json) {
    return UnavailableOrderItem(
      name: json['name'] as String? ?? '',
      windows: (json['windows'] as List? ?? const [])
          .whereType<String>()
          .toList(growable: false),
      label: json['label'] as String? ?? '',
    );
  }
}

class OrderAvailabilityResponse {
  const OrderAvailabilityResponse({
    required this.blocked,
    required this.unavailableNames,
    required this.unavailableItems,
    required this.interactiveNote,
    this.itemsMessage,
    this.note,
    this.message,
  });

  final bool blocked;
  final List<String> unavailableNames;
  final List<UnavailableOrderItem> unavailableItems;
  final String? itemsMessage;
  final String? note;
  final String? message;
  final bool interactiveNote;

  factory OrderAvailabilityResponse.fromJson(Map<String, dynamic> json) {
    return OrderAvailabilityResponse(
      blocked: json['blocked'] as bool? ?? false,
      unavailableNames: (json['unavailable_names'] as List? ?? const [])
          .whereType<String>()
          .toList(growable: false),
      unavailableItems: (json['unavailable_items'] as List? ?? const [])
          .whereType<Map>()
          .map((item) =>
              UnavailableOrderItem.fromJson(Map<String, dynamic>.from(item)))
          .toList(growable: false),
      itemsMessage: json['items_message'] as String?,
      note: json['note'] as String?,
      message: json['message'] as String?,
      interactiveNote: json['interactive_note'] as bool? ?? false,
    );
  }
}

class VoucherPreviewRequest {
  const VoucherPreviewRequest({
    required this.voucherCode,
    required this.fulfillmentMethod,
    required this.subtotal,
    this.branchId,
    this.shippingFee = 0,
    this.customerEmail,
    this.customerPhone,
  });

  final String voucherCode;
  final int? branchId;
  final String fulfillmentMethod;
  final int subtotal;
  final int shippingFee;
  final String? customerEmail;
  final String? customerPhone;

  Map<String, dynamic> toJson() => {
        'voucher_code': voucherCode.trim(),
        'branch_id': branchId,
        'fulfillment_method': fulfillmentMethod,
        'subtotal': subtotal,
        'shipping_fee': shippingFee,
        'customer_email':
            customerEmail == null || customerEmail!.trim().isEmpty
                ? null
                : customerEmail!.trim(),
        'customer_phone':
            customerPhone == null || customerPhone!.trim().isEmpty
                ? null
                : customerPhone!.trim(),
      };
}

class VoucherPreviewResponse {
  const VoucherPreviewResponse({
    required this.valid,
    required this.discountTotal,
    required this.subtotal,
    required this.shippingFee,
    required this.total,
    this.message,
    this.voucher,
  });

  final bool valid;
  final String? message;
  final VoucherInfo? voucher;
  final int discountTotal;
  final int subtotal;
  final int shippingFee;
  final int total;

  factory VoucherPreviewResponse.fromJson(Map<String, dynamic> json) {
    return VoucherPreviewResponse(
      valid: json['valid'] as bool? ?? false,
      message: json['message'] as String?,
      voucher: json['voucher'] is Map<String, dynamic>
          ? VoucherInfo.fromJson(json['voucher'] as Map<String, dynamic>)
          : null,
      discountTotal: (json['discount_total'] as num?)?.toInt() ?? 0,
      subtotal: (json['subtotal'] as num?)?.toInt() ?? 0,
      shippingFee: (json['shipping_fee'] as num?)?.toInt() ?? 0,
      total: (json['total'] as num?)?.toInt() ?? 0,
    );
  }
}

class VoucherInfo {
  const VoucherInfo({
    required this.code,
    required this.name,
    this.description,
    this.type,
    this.valueLabel,
  });

  final String code;
  final String name;
  final String? description;
  final String? type;
  final String? valueLabel;

  factory VoucherInfo.fromJson(Map<String, dynamic> json) {
    return VoucherInfo(
      code: json['code'] as String? ?? '',
      name: json['name'] as String? ?? '',
      description: json['description'] as String?,
      type: json['type'] as String?,
      valueLabel: json['value_label'] as String?,
    );
  }
}

class OrderResponse {
  const OrderResponse({
    required this.id,
    required this.code,
    required this.status,
    required this.statusLabel,
    required this.paymentMethod,
    required this.paymentStatus,
    required this.fulfillmentMethod,
    required this.fulfillmentLabel,
    required this.subtotal,
    required this.shippingFee,
    required this.discountTotal,
    this.voucherCode,
    required this.total,
    required this.customerName,
    required this.customerPhone,
    this.deliveryAddress,
    this.invoiceNumber,
    this.branch,
    this.items = const [],
    this.timeline = const [],
    this.requestedDate,
    this.requestedTime,
    this.createdAt,
  });

  final int id;
  final String code;
  final String status;
  final String statusLabel;
  final String paymentMethod;
  final String paymentStatus;
  final String fulfillmentMethod;
  final String fulfillmentLabel;
  final int subtotal;
  final int shippingFee;
  final int discountTotal;
  final String? voucherCode;
  final int total;
  final String customerName;
  final String customerPhone;
  final String? deliveryAddress;
  final String? invoiceNumber;
  final OrderBranch? branch;
  final List<OrderLineItem> items;
  final List<OrderTimelineStep> timeline;
  final String? requestedDate;
  final String? requestedTime;
  final String? createdAt;

  factory OrderResponse.fromJson(Map<String, dynamic> json) {
    final rawItems = json['items'] as List? ?? const [];
    final rawTimeline = json['timeline'] as List? ?? const [];
    return OrderResponse(
      id: (json['id'] as num?)?.toInt() ?? 0,
      code: json['code'] as String? ?? '',
      status: json['status'] as String? ?? '',
      statusLabel: json['status_label'] as String? ??
          _fallbackStatusLabel(json['status'] as String? ?? ''),
      paymentMethod: json['payment_method'] as String? ?? 'offline',
      paymentStatus: json['payment_status'] as String? ?? 'unpaid',
      fulfillmentMethod: json['fulfillment_method'] as String? ?? 'pickup',
      fulfillmentLabel: json['fulfillment_label'] as String? ??
          _fallbackFulfillmentLabel(
            json['fulfillment_method'] as String? ?? 'pickup',
          ),
      subtotal: (json['subtotal'] as num?)?.toInt() ?? 0,
      shippingFee: (json['shipping_fee'] as num?)?.toInt() ?? 0,
      discountTotal: (json['discount_total'] as num?)?.toInt() ?? 0,
      voucherCode: json['voucher_code'] as String?,
      total: (json['total'] as num?)?.toInt() ?? 0,
      customerName: json['customer_name'] as String? ?? '',
      customerPhone: json['customer_phone'] as String? ?? '',
      deliveryAddress: json['delivery_address'] as String?,
      invoiceNumber: json['invoice_number'] as String?,
      branch: json['branch'] is Map<String, dynamic>
          ? OrderBranch.fromJson(json['branch'] as Map<String, dynamic>)
          : null,
      items: rawItems
          .whereType<Map>()
          .map((item) => OrderLineItem.fromJson(Map<String, dynamic>.from(item)))
          .toList(),
      timeline: rawTimeline
          .whereType<Map>()
          .map((item) =>
              OrderTimelineStep.fromJson(Map<String, dynamic>.from(item)))
          .toList(),
      requestedDate: json['requested_date'] as String?,
      requestedTime: json['requested_time'] as String?,
      createdAt: json['created_at'] as String?,
    );
  }
}

class OrderLineItem {
  const OrderLineItem({
    required this.dishName,
    required this.unitPrice,
    required this.quantity,
    required this.lineTotal,
    this.note,
  });

  final String dishName;
  final int unitPrice;
  final int quantity;
  final int lineTotal;
  final String? note;

  factory OrderLineItem.fromJson(Map<String, dynamic> json) {
    return OrderLineItem(
      dishName: json['dish_name'] as String? ?? '',
      unitPrice: (json['unit_price'] as num?)?.toInt() ?? 0,
      quantity: (json['quantity'] as num?)?.toInt() ?? 0,
      lineTotal: (json['line_total'] as num?)?.toInt() ?? 0,
      note: json['note'] as String?,
    );
  }
}

class OrderTimelineStep {
  const OrderTimelineStep({
    required this.status,
    required this.label,
    required this.completed,
    required this.current,
    this.icon,
  });

  final String status;
  final String label;
  final bool completed;
  final bool current;
  final String? icon;

  factory OrderTimelineStep.fromJson(Map<String, dynamic> json) {
    return OrderTimelineStep(
      status: json['status'] as String? ?? '',
      label: json['label'] as String? ??
          _fallbackStatusLabel(json['status'] as String? ?? ''),
      completed: json['completed'] as bool? ?? false,
      current: json['current'] as bool? ?? false,
      icon: json['icon'] as String?,
    );
  }
}

class OrderBranch {
  const OrderBranch({
    required this.id,
    required this.name,
    this.address,
    this.phone,
  });

  final int id;
  final String name;
  final String? address;
  final String? phone;

  factory OrderBranch.fromJson(Map<String, dynamic> json) {
    return OrderBranch(
      id: (json['id'] as num?)?.toInt() ?? 0,
      name: json['name'] as String? ?? '',
      address: json['address'] as String?,
      phone: json['phone'] as String?,
    );
  }
}

String _fallbackStatusLabel(String status) {
  return switch (status) {
    'pending' => 'Chờ xác nhận',
    'confirmed' => 'Đã xác nhận',
    'preparing' => 'Đang chuẩn bị',
    'ready' => 'Sẵn sàng',
    'shipping' => 'Đang giao',
    'completed' => 'Hoàn tất',
    'cancelled' => 'Đã huỷ',
    _ => status,
  };
}

String _fallbackFulfillmentLabel(String method) {
  return switch (method) {
    'delivery' => 'Giao hàng',
    'pickup' => 'Tự đến lấy',
    _ => method,
  };
}
