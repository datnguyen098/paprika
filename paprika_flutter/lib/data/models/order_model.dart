import 'cart_model.dart';

class CreateOrderRequest {
  const CreateOrderRequest({
    required this.branchId,
    required this.customerName,
    required this.customerPhone,
    required this.fulfillmentMethod,
    required this.items,
    this.customerEmail,
    this.deliveryAddress,
    this.requestedDate,
    this.requestedTime,
    this.note,
  });

  final int branchId;
  final String customerName;
  final String customerPhone;
  final String? customerEmail;
  final String fulfillmentMethod;
  final String? deliveryAddress;
  final String? requestedDate;
  final String? requestedTime;
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
        'requested_date': requestedDate,
        'requested_time': requestedTime,
        'note': note == null || note!.trim().isEmpty ? null : note!.trim(),
        'items': items.map((item) => item.toOrderJson()).toList(),
      };
}

class OrderResponse {
  const OrderResponse({
    required this.id,
    required this.code,
    required this.status,
    required this.paymentMethod,
    required this.paymentStatus,
    required this.fulfillmentMethod,
    required this.subtotal,
    required this.shippingFee,
    required this.discountTotal,
    required this.total,
    required this.customerName,
    required this.customerPhone,
    this.deliveryAddress,
    this.invoiceNumber,
    this.branch,
    this.createdAt,
  });

  final int id;
  final String code;
  final String status;
  final String paymentMethod;
  final String paymentStatus;
  final String fulfillmentMethod;
  final int subtotal;
  final int shippingFee;
  final int discountTotal;
  final int total;
  final String customerName;
  final String customerPhone;
  final String? deliveryAddress;
  final String? invoiceNumber;
  final OrderBranch? branch;
  final String? createdAt;

  factory OrderResponse.fromJson(Map<String, dynamic> json) {
    return OrderResponse(
      id: (json['id'] as num?)?.toInt() ?? 0,
      code: json['code'] as String? ?? '',
      status: json['status'] as String? ?? '',
      paymentMethod: json['payment_method'] as String? ?? 'offline',
      paymentStatus: json['payment_status'] as String? ?? 'unpaid',
      fulfillmentMethod: json['fulfillment_method'] as String? ?? 'pickup',
      subtotal: (json['subtotal'] as num?)?.toInt() ?? 0,
      shippingFee: (json['shipping_fee'] as num?)?.toInt() ?? 0,
      discountTotal: (json['discount_total'] as num?)?.toInt() ?? 0,
      total: (json['total'] as num?)?.toInt() ?? 0,
      customerName: json['customer_name'] as String? ?? '',
      customerPhone: json['customer_phone'] as String? ?? '',
      deliveryAddress: json['delivery_address'] as String?,
      invoiceNumber: json['invoice_number'] as String?,
      branch: json['branch'] is Map<String, dynamic>
          ? OrderBranch.fromJson(json['branch'] as Map<String, dynamic>)
          : null,
      createdAt: json['created_at'] as String?,
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
