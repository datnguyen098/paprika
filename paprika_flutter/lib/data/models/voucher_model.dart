class PublicVoucher {
  const PublicVoucher({
    required this.id,
    required this.code,
    required this.name,
    required this.discountType,
    required this.valueLabel,
    required this.minOrderAmount,
    required this.isDefault,
    this.description,
    this.maxDiscountAmount,
    this.startsAt,
    this.endsAt,
    this.usageLimitTotal,
    this.usageLimitPerCustomer,
    this.usedCount = 0,
    this.branch,
  });

  final int id;
  final String code;
  final String name;
  final String discountType;
  final String valueLabel;
  final String? description;
  final int minOrderAmount;
  final int? maxDiscountAmount;
  final String? startsAt;
  final String? endsAt;
  final int? usageLimitTotal;
  final int? usageLimitPerCustomer;
  final int usedCount;
  final bool isDefault;
  final VoucherBranch? branch;

  factory PublicVoucher.fromJson(Map<String, dynamic> json) {
    return PublicVoucher(
      id: (json['id'] as num?)?.toInt() ?? 0,
      code: json['code'] as String? ?? '',
      name: json['name'] as String? ?? '',
      description: json['description'] as String?,
      discountType: json['discount_type'] as String? ?? '',
      valueLabel: json['value_label'] as String? ?? '',
      minOrderAmount: (json['min_order_amount'] as num?)?.toInt() ?? 0,
      maxDiscountAmount: (json['max_discount_amount'] as num?)?.toInt(),
      startsAt: json['starts_at'] as String?,
      endsAt: json['ends_at'] as String?,
      usageLimitTotal: (json['usage_limit_total'] as num?)?.toInt(),
      usageLimitPerCustomer:
          (json['usage_limit_per_customer'] as num?)?.toInt(),
      usedCount: (json['used_count'] as num?)?.toInt() ?? 0,
      isDefault: json['is_default'] as bool? ?? false,
      branch: json['branch'] is Map<String, dynamic>
          ? VoucherBranch.fromJson(json['branch'] as Map<String, dynamic>)
          : null,
    );
  }
}

class VoucherBranch {
  const VoucherBranch({
    required this.id,
    required this.name,
  });

  final int id;
  final String name;

  factory VoucherBranch.fromJson(Map<String, dynamic> json) {
    return VoucherBranch(
      id: (json['id'] as num?)?.toInt() ?? 0,
      name: json['name'] as String? ?? '',
    );
  }
}
